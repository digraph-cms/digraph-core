<?php

namespace DigraphCMS\HTTP;

class ResponseHeaders extends AbstractHeaders
{
    protected $response = null;
    protected $private = false;

    public function private(bool $private = null): bool
    {
        if ($private !== null) {
            $this->private = $private;
        }
        return $this->private;
    }

    public function response(Response $response = null): Response
    {
        if ($response) {
            $this->response = $response;
        }
        return $this->response;
    }

    public function get_Cache_Control(): string
    {
        $staleTTL = $this->response->staleTTL();
        $output = [
            $this->private ? 'no-store' : 'public',
            'max-age=' . $this->response->browserTTL(),
            'max-stale=' . $staleTTL,
            'stale-if-error=' . $staleTTL,
        ];
        return implode(', ', array_filter($output));
    }

    public function toArray(): array
    {
        $headers = parent::toArray();
        $headers['Pragma'] = $this->get('Pragma');
        $headers['Cache-Control'] = $this->get('Cache-Control');
        return array_filter($headers);
    }
}
