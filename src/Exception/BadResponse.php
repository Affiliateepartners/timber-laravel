<?php
namespace Liteweb\TimberLaravel\Exception;

class BadResponse extends \Exception
{
	private $response;

	public function __construct(\Psr\Http\Message\ResponseInterface $response, ?string $message = null)
	{
		$this->response = $response;

		parent::__construct($message ?? 'Invalid response code');
	}

	public function getResponse(): \Psr\Http\Message\ResponseInterface
	{
		return $this->response;
	}
}
