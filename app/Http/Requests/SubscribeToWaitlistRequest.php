<?php

namespace App\Http\Requests;

use App\Enums\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubscribeToWaitlistRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['bail', 'required', 'string', 'email', 'max:255'],
            'locale' => ['required', Rule::enum(Language::class)],
            'consent' => ['accepted'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        $this->merge([
            'email' => is_string($email) ? Str::lower(trim($email)) : $email,
            'locale' => $this->input('locale', App::currentLocale()),
        ]);
    }
}
