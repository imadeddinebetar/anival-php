<?php

namespace Core\Http\Message;

use Core\Exceptions\Internal\HttpException;
use Core\Validation\Contracts\ValidatorFactoryInterface;
use Core\Validation\Internal\ValidationException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
abstract class FormRequest extends Request
{
    public function __construct(
        ServerRequestInterface $request,
        protected ValidatorFactoryInterface $validatorFactory
    ) {
        parent::__construct($request);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string|array<mixed>>
     */
    abstract public function rules(): array;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Validate the request data.
     *
     * @param array<string, string> $rules
     * @param array<string, string> $messages
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function validate(array $rules, array $messages = []): array
    {
        $data = array_merge($this->getQueryParams(), (array)$this->getParsedBody());

        $validator = $this->validatorFactory->make($data);

        if (!$validator->validate($rules, $messages)) {
            throw new ValidationException($validator->errors());
        }

        return $data;
    }

    /**
     * Validate the class instance.
     *
     * @throws ValidationException
     * @throws \Exception
     */
    public function validateResolved(): void
    {
        if (!$this->authorize()) {
            throw new HttpException(403, 'This action is unauthorized.');
        }

        $this->validate($this->rules(), $this->messages());
    }
}
