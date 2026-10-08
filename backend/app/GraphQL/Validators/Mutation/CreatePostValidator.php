<?php

namespace App\GraphQL\Validators\Mutation;

use Nuwave\Lighthouse\Validation\Validator;

class CreatePostValidator extends Validator
{
    /**
     * Validation rules for the createPost mutation.
     *
     * Keys refer to the client-facing argument names in the schema.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'userId' => ['required', 'exists:users,id'],
            'body' => ['required', 'string', 'max:280'],
        ];
    }
}
