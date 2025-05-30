<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StreamCoverUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file' => 'required|mimes:'.getSetting('media.allowed_file_extensions').'|max:'.(getSetting('media.max_file_upload_size') ? ((int)getSetting('media.max_file_upload_size') * 1000) : 4000),
        ];
    }
}
