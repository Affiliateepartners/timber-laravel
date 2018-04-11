<?php
namespace Liteweb\TimberLaravel\Exception;

class BadResponse extends \Exception
{
	private $response;

	public function __construct(\Psr\Http\Message\ResponseInterface $response)
	{
		$this->response = $response;

		parent::__construct('Invalid response code');
	}

	public function getResponse(): \Psr\Http\Message\ResponseInterface
	{
		return $this->response;
	}
}
