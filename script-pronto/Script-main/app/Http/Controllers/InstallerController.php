<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstallerSaveAdminInfoRequest;
use App\Http\Requests\InstallerSaveDBInfoRequest;
use App\Providers\AuthServiceProvider;
use App\Providers\GenericHelperServiceProvider;
use App\Providers\InstallerServiceProvider;
use App\Providers\ListsHelperServiceProvider;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PDO;

class InstallerController extends Controller
{
    /**
     * Renders installer steps views.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function install(Request $request)
    {
        if (InstallerServiceProvider::checkIfInstalled()) {
            return Redirect::to(route('home'));
        }
        $step = $request->get('step') ? (int) $request->get('step') : 1;
        if ($step == 1) {
            $phpExtensions = InstallerServiceProvider::getRequiredExtensions();

            return view('installer.requirements', [
                'requiredExtensions' => $phpExtensions,
                'passesRequirements' => InstallerServiceProvider::passesRequirements(),
            ]);
        } elseif ($step == 2) {
            return view('installer.database');
        } elseif ($step == 3) {
            return view('installer.admin');
        }
    }

    /**
     * Checks if db connections is valid, if so, it saves it into the session.
     *
     * @param InstallerSaveDBInfoRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function testAndSaveDBInfo(InstallerSaveDBInfoRequest $request)
    {
        if (InstallerServiceProvider::checkIfInstalled()) {
            return Redirect::to(route('home'));
        }

        $db_host = $request->get('db_host');
        $db_port = $request->get('db_port') ? $request->get('db_port') : config('database.connections.mysql.port');
        $db_name = $request->get('db_name');
        $db_username = $request->get('db_username');
        $db_password = $request->get('db_password');
        try {
            $dbCheck = new PDO("mysql:host={$db_host};port=".$db_port.";dbname={$db_name}", $db_username, $db_password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            session([
                'db_host' => $db_host,
                'db_port' => $db_port,
                'db_name' => $db_name,
                'db_username' => $db_username,
                'db_password' => $db_password,
            ]);
        } catch (\PDOException $ex) {
            return Redirect::to(route('installer.install').'?step=2')
                ->with('error', __('Database connection could not be established:').' '.$ex->getMessage());
        }

        return Redirect::to(route('installer.install').'?step=3'); // warning
    }

    /**
     * Starts the installation. Sets up .env and redirect to next step.
     *
     * @param InstallerSaveAdminInfoRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function beginInstall(InstallerSaveAdminInfoRequest $request)
    {
        if (InstallerServiceProvider::checkIfInstalled()) {
            return Redirect::to(route('home'));
        }

        if (!$request->session()->get('db_host')) {
            return Redirect::to(route('installer.install').'?step=3')
                ->with('error', __('Database connection could not be established. Go back to previous step.'));
        }

        $site_title = $request->get('site_title');
        $app_url = $request->get('app_url');
        $email = $request->get('email');
        $password = $request->get('password');
        $licenseCode = $request->get('license');

        $license = InstallerServiceProvider::gld($licenseCode);
        if (isset($license->error)) {
            return Redirect::to(route('installer.install').'?step=3')
                ->with('error', $license->error);
        }
        session(['license' => json_encode(array_merge((array)$license, ['code'=>$licenseCode]))]);
        session(['licenseCode' => $licenseCode]);
        if(!$this->saveEnvValues($request)){
            return Redirect::to(route('installer.install').'?step=3')
                ->with('error', InstallerServiceProvider::$acError);
        }

        return redirect()->route('installer.finishInstall')->with([
            'site_title' => $site_title,
            'email' => $email,
            'password' => $password,
            'app_url' => $app_url,
        ]);
    }

    /**
     * Doing the actual installation.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function finishInstall()
    {
        if (InstallerServiceProvider::checkIfInstalled()) {
            return Redirect::to(route('home'));
        }

        $site_title = session()->get('site_title');
        $app_url = session()->get('app_url');
        $email = session()->get('email');
        $password = session()->get('password');

        try {
            Schema::defaultStringLength(191);
            Artisan::call('migrate', ['--force'=>true]);
            Artisan::call('db:seed', ['--force'=>true]);
        } catch (\Exception $e) {
            throw new \Exception(__('Migrations or Seeds failed with following message').' "'.$e->getMessage().'"" '.__('Please re-create your database and try again. If error persists, please contact us.'));
        }

        // 2. Site settings
        DB::statement("UPDATE settings SET `value` = :title WHERE `key` = 'site.name'", ['title'=>$site_title]);
        DB::statement("UPDATE settings SET `value` = :url WHERE `key` = 'site.app_url'", ['url'=>$app_url]);
        DB::statement("UPDATE settings SET `value` = :val WHERE `key` = 'license.product_license_key'", ['val'=> session()->get('licenseCode')]);

        // 3. Add user & make it admin
        $user = AuthServiceProvider::createUser([
            'name' => 'Admin',
            'email' => $email,
            'password' => $password,
            'email_verified_at' => Carbon::now(),
        ]);
        User::where('email', $email)->update(['role_id' => 1]);
        GenericHelperServiceProvider::createUserWallet($user);
        ListsHelperServiceProvider::createUserDefaultLists($user->id);

        // 4. Create an installed file over public dir
        Storage::disk('local')->put('installed', session()->get('license'));

        // 5. Create storage symlink
        Artisan::call('storage:link');

        // We could even do this to cache the config/avoid Xampp on Windows .env related issues
        // php artisan config:cache

        return Redirect::to($app_url);
    }

    /**
     * Renders upgrade view.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function upgrade()
    {
        if(!(Auth::check() && Auth::user()->role_id == 1)){
            return redirect(route('home'));
        }
        $canMigrate = InstallerServiceProvider::hasAvailableMigrations();

        return view('installer.upgrade', ['canMigrate'=>$canMigrate]);
    }

    /**
     * Runs the update.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function doUpgrade()
    {
        try {
            Artisan::call('down');
            Artisan::call('migrate', ['--force'=>true]);
            Artisan::call('up');
        } catch (\Exception $e) {
            Redirect::to(route('installer.update'))->with('error', $e->getMessage());
        }

        return Redirect::to(route('installer.update'))->with('success', __('Database updated successfully.'));
    }

    /**
     * Saves db info to env file.
     * @param $request
     */
    public function saveEnvValues($request) {
        if(InstallerServiceProvider::getLockCode()){
            InstallerServiceProvider::appendToEnv('DB_HOST='.'"'.$request->session()->get('db_host').'"');
            InstallerServiceProvider::appendToEnv('DB_DATABASE='.'"'.$request->session()->get('db_name').'"');
            InstallerServiceProvider::appendToEnv('DB_USERNAME='.'"'.$request->session()->get('db_username').'"');
            InstallerServiceProvider::appendToEnv('DB_PORT='.'"'.$request->session()->get('db_port').'"');
            InstallerServiceProvider::appendToEnv('DB_PASSWORD='.'"'.$request->session()->get('db_password').'"');
            return true;
        }
        else{
            return false;
        }
    }
}
