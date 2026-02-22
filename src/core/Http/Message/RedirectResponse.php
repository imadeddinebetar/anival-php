<?php

namespace Core\Http\Message;

use Core\Session\Contracts\SessionInterface;

/**
 * @internal
 */
class RedirectResponse extends Response
{
    /**
     * @param string $url
     * @param int $status
     * @param array $headers
     */
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        $headers['Location'] = $url;
        parent::__construct('', $status, $headers);
    }

    /**
     * Flash a piece of data to the session.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function with(string $key, mixed $value): self
    {
        $this->getSession()->flash($key, $value);
        return $this;
    }

    /**
     * Flash validation errors to the session.
     *
     * @param array $errors
     * @return $this
     */
    public function withErrors(array $errors): self
    {
        $this->getSession()->flash('errors', $errors);
        return $this;
    }

    /**
     * Get the session instance.
     *
     * @return SessionInterface
     */
    protected function getSession(): SessionInterface
    {
        return container()->get(SessionInterface::class);
    }
}
