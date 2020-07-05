<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MaxUploadSize implements Rule
{
    private $max_size;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->max_size = (int)ini_get('upload_max_filesize') * 1000;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $client_file_size = $value->getSize();
        return $client_file_size <= $this->max_size;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The file size must be equals or less than ' . $this->max_size;
    }
}
