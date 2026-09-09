<?php

namespace App\Http\Requests;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreApplicantRequest extends FormRequest
{
    private const MAX_TOTAL_UPLOAD_BYTES = 10 * 1024 * 1024;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', (string) $this->birth_date, $m)) {
            $this->merge(['birth_date' => "{$m[3]}-{$m[2]}-{$m[1]}"]);
        }
    }

    public function rules(): array
    {
        $fileRule = app(SettingsService::class)->get('registration.document_upload_disabled', false) ? ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'] : ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'];

        return ['submission_uuid' => ['required', 'uuid'], 'payment_method' => ['sometimes', 'nullable', 'string', 'max:30'], 'full_name' => ['required', 'string', 'min:3', 'max:150'], 'birth_place' => ['required', 'string', 'min:2', 'max:100'], 'birth_date' => ['required', 'date', 'before_or_equal:today'], 'address' => ['required', 'string', 'max:2000'], 'whatsapp' => ['required', 'string', 'max:30'], 'email' => ['required', 'email:rfc', 'max:190'], 'consent' => ['accepted'], ...collect(['recommendation_letter', 'diploma', 'photo_4x6', 'identity_card', 'pddikti_screenshot'])->mapWithKeys(fn ($key) => [$key => $fileRule])->all()];
    }

    public function messages(): array
    {
        return ['required' => 'Kolom :attribute wajib diisi.', 'accepted' => 'Persetujuan wajib diberikan.', 'mimes' => 'Berkas :attribute harus berformat JPG, JPEG, PNG, atau PDF.', 'max' => 'Ukuran :attribute tidak boleh melebihi 10 MB.'];
    }

    public function attributes(): array
    {
        return [
            'recommendation_letter' => 'surat rekomendasi',
            'diploma' => 'ijazah',
            'photo_4x6' => 'foto 4×6',
            'identity_card' => 'KTP',
            'pddikti_screenshot' => 'Screenshot PDDIKTI / Penyetaraan',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $totalSize = collect(['recommendation_letter', 'diploma', 'photo_4x6', 'identity_card', 'pddikti_screenshot'])
                ->sum(fn (string $key) => (int) ($this->file($key)?->getSize() ?? 0));

            if ($totalSize > self::MAX_TOTAL_UPLOAD_BYTES) {
                $validator->errors()->add('documents', 'Total ukuran seluruh dokumen tidak boleh melebihi 10 MB. Kompres atau perkecil file sebelum mengirim.');
            }
        });
    }
}
