<?php
namespace OCA\LimitLoginIp\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;

class LoginDeniedController extends Controller
{
	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoCSRFRequired
	 * @PublicPage
	 */
	public function showErrorPage()
	{
		$response = new TemplateResponse(
			$this->appName,
			'errorPage',
			[],
			'guest'
		);
		$response->setStatus(Http::STATUS_FORBIDDEN);

		return $response;
	}
}
