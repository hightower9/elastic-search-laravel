<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filter[search]'    => ['nullable', 'string', 'max:255'],
            'filter[status]'    => ['nullable', 'string', 'max:255'],
            'filter[qa_status]' => ['nullable', 'string', 'max:255'],
            'sort'              => ['nullable', 'string', 'max:255'],
            'page'              => ['nullable', 'integer', 'min:1'],
            'paginate'          => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Sends the validated data array.
     *
     * @param  array|int|string|null  $key
     * @param  mixed  $default
     * @return array
     */
    public function validated($key = NULL, $default = NULL): array
    {
        return collect(parent::validated($key, $default))
            ->merge([
                'filter' => [
                    'search'    => $this->input('filter')['search'] ?? '',
                    'status'    => isset($this->input('filter')['status']) ? explode(',', $this->input('filter')['status']) : NULL,
                    'qa_status' => isset($this->input('filter')['qa_status']) ? explode(',', $this->input('filter')['qa_status']) : NULL,
                ],
                'sort'     => $this->input('sort') != NULL ? explode(',', $this->input('sort')) : NULL,
                'page'     => $this->input('page', 1),
                'paginate' => $this->input('paginate') <= 200 ? $this->input('paginate', 10) : 10,
            ])->toArray();
    }
}
