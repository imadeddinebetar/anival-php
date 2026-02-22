<?php

namespace Core\Validation\Traits;

use Core\Validation\Internal\Validator;

/**
 * Provides a validate() method for controllers — inline validation.
 *
 * Usage in controllers:
 *   $validated = $this->validate($request, [
 *       'email' => 'required|email',
 *       'name'  => 'required|min:2',
 *   ]);
 */
trait ValidatesRequests
{
    /**
     * Validate the given request with the given rules.
     *
     * @param mixed $request  PSR-7 ServerRequest or Request wrapper
     * @param array<string, string> $rules
     * @param array<string, string> $messages
     * @return array<string, mixed> The validated data
     *
     * @throws \Core\Validation\Internal\ValidationException
     */
    protected function validate(mixed $request, array $rules, array $messages = []): array
    {
        $data = $this->extractData($request);

        $validator = new Validator($data);

        if (!empty($messages)) {
            $validator->setCustomMessages($messages);
        }

        $validator->validate($rules);
        return $validator->validated();
    }

    /**
     * Extract input data from the request.
     *
     * @param mixed $request
     * @return array<string, mixed>
     */
    private function extractData(mixed $request): array
    {
        // PSR-7 ServerRequestInterface
        if (method_exists($request, 'getParsedBody')) {
            $body = $request->getParsedBody();
            $query = method_exists($request, 'getQueryParams') ? $request->getQueryParams() : [];
            return array_merge($query, is_array($body) ? $body : []);
        }

        // Raw array
        if (is_array($request)) {
            return $request;
        }

        return [];
    }
}
