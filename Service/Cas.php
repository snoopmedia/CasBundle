<?php

namespace Sensio\CasBundle\Service;

use Sensio\CasBundle\Service\Protocol\V1Protocol;
use Sensio\CasBundle\Service\Protocol\V2Protocol;

use Sensio\CasBundle\Service\Request\CurlRequest;
use Sensio\CasBundle\Service\Request\HttpRequest;
use Sensio\CasBundle\Service\Request\FileRequest;

use Sensio\CasBundle\Service\Response\V1Response;
use Sensio\CasBundle\Service\Response\V2Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \Symfony\Bundle\FrameworkBundle\Routing\Router;

class Cas
{
    protected
        $protocol,
        $version,
        $certFile,
        $requestType,
        $authService,
        $router;

    public function __construct($baseUri, $version = 2, $certFile = null, $requestType = 'curl', $authService = 'cas', Router $router)
    {
        $this->version = $version;
        $this->certFile = $certFile;
        $this->requestType = $requestType;
        $this->authService = $authService;
        $this->router = $router;
        $this->protocol = $this->getProtocol($baseUri, $version);
    }

    public function getValidation(Request $request)
    {
        $uri = $this->protocol->getValidationUri($request->getUri(), $request->query->get('ticket'));

        return $this->getRequest($uri)
            ->setCertFile($this->certFile)
            ->send($this->getResponse())
            ->getResponse();
    }

    public function getLogoutResponse(Request $request)
    {
        $uri = $this->protocol->getLogoutUri($request->getUri());

        return new RedirectResponse($uri);
    }

    public function getLoginResponse(Request $request)
    {
        if ($this->authService == 'cas') {
            $uri = $this->protocol->getLoginUri($request->getUri());
        } else {
            // @todo This is a little bit of a hack
            $uri = $this->router->generate('saml_login', array(), Router::ABSOLUTE_URL);
        }

        return new RedirectResponse($uri);
    }

    public function isValidationRequest(Request $request)
    {
        return $request->query->has('ticket');
    }

    protected function getProtocol($baseUri)
    {
        switch((int) $this->version) {
            case 1:
                return new V1Protocol($baseUri);
            case 2:
                return new V2Protocol($baseUri);
            default:
                throw new \Exception('Invalid CAS version : '.$this->version);
        }
    }

    protected function getResponse()
    {
        switch ((int) $this->version) {
            case 1:
                return new V1Response();
            case 2:
                return new V2Response();
            default:
                throw new \Exception('Invalid CAS version : '.$this->version);
        }
    }

    protected function getRequest($uri)
    {
        switch (strtolower($this->requestType)) {
            case 'curl':
                return new CurlRequest($uri);
            case 'http':
                return new HttpRequest($uri);
            case 'file':
                return new FileRequest($uri);
            default:
                throw new \Exception('Invalid CAS request type : '.$this->requestType);
        }
    }
}
