<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $merge = [];
        if ($this->filled('name') && !$this->filled('title')) {
            $merge['title'] = $this->input('name');
        }
        if ($this->filled('description') && !$this->filled('notes')) {
            $merge['notes'] = $this->input('description');
        }
        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The expense name is required.',
            'expense_category_id.required' => 'Please select an expense category.',
            'expense_category_id.exists' => 'The selected expense category is invalid.',
            'amount.required' => 'The expense amount is required.',
            'amount.min' => 'The expense amount must be at least $0.01.',
            'expense_date.required' => 'Please enter the expense date.',
        ];
    }
}
