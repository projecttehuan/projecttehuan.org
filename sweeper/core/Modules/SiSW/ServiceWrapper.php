<?php
namespace Swiftriver\Core\Modules\SiSW;
class ServiceWrapper
{

    /**
     * The Uri of the service
     * @var string
     */
    private $uri;

    /**
     * The address of the proxy server if one is set
     * @var string|null
     */
    private $proxy;

    /**
     * The username for the proxy server if one is set
     * @var string|null
     */
    private $proxyUsername;

    /**
     * The password for the proxy server if one is set
     * @var string|null
     */
    private $proxyPassword;


    /**
     * Constructor Method
     * @param string $uri
     */
    public function __construct($uri) {
        $this->uri = $uri;
        $this->proxy = \Swiftriver\Core\Setup::Configuration()->ProxyServer;
        $this->proxyUsername = \Swiftriver\Core\Setup::Configuration()->ProxyServerUserName;
        $this->proxyPassword = \Swiftriver\Core\Setup::Configuration()->ProxyServerPassword;
    }

    /**
     *
     * @param array $postData
     * @param int $timeout
     * @param array $contextAdditions
     * @return string
     */
    public function MakePOSTRequest($postData, $timeout, $contextAdditions = null)
    {
        $context = array
        (
            'http' => array
            (
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($postData, '', '&'),
                'timeout' => $timeout,
            )
        );

        if($contextAdditions != null)
            foreach($contextAdditions as $key => $value)
                $context["http"][$key] = $value;

        if($this->proxy != null && $this->proxy != "")
        {
            $context["http"]["proxy"] = $this->proxy;
            $context["http"]["request_fulluri"] = true;

            if($this->proxyUsername != null && $this->proxyUsername != "" && $this->proxyPassword != null && $this->proxyPassword != "")
            {
                $auth = \base64_encode($this->proxyUsername . ":" . $this->proxyPassword);
                $context["http"]["header"] = $context["http"]["header"] . "\r\nProxy-Authorization: Basic $auth";
            }
        }
        
        $context = stream_context_create($context);

        $returnData = file_get_contents($this->uri, false, $context);

        return $returnData;
    }

    /**
     *
     * @param string $json
     * @param int $timeout
     * @return string
     */
    public function MakeJSONPOSTRequest($json, $timeout) {
        $context = array
        (
            'http' => array
            (
                'method' => 'POST',
                'header' => 'Content-type: application/json; charset=utf-8',
                'content' => $json,
                'timeout' => $timeout,
            ),
        );

                if($this->proxy != null && $this->proxy != "")
        {
            $context["http"]["proxy"] = $this->proxy;
            $context["http"]["request_fulluri"] = true;

            if($this->proxyUsername != null && $this->proxyUsername != "" && $this->proxyPassword != null && $this->proxyPassword != "")
            {
                $auth = \base64_encode($this->proxyUsername . ":" . $this->proxyPassword);
                $context["http"]["header"] = $context["http"]["header"] . "\r\nProxy-Authorization: Basic $auth";
            }
        }

        $context = stream_context_create($context);

        $returnData = file_get_contents($this->uri, false, $context);

        return $returnData;
    }

    /**
     *
     */
    public function MakeGETRequest($contextAdditions = null) {
        
        $context = null;

        if($this->proxy != null && $this->proxy != "")
        {
            $context = array ( "http" => array() );
            
            $context["http"]["proxy"] = $this->proxy;
            $context["http"]["request_fulluri"] = true;

            if($this->proxyUsername != null && $this->proxyUsername != "" && $this->proxyPassword != null && $this->proxyPassword != "")
            {
                $auth = \base64_encode($this->proxyUsername . ":" . $this->proxyPassword);
                $context["http"]["header"] = "Proxy-Authorization: Basic $auth";
            }
        }

        if($contextAdditions != null)
        {
            if($context == null)
                $context = array ( "http" => array() );

            foreach($contextAdditions as $key => $value)
                $context["http"][$key] = $value;
        }

        if($context != null)
            $context = stream_context_create($context);

        $returnData = file_get_contents($this->uri, false, $context);

        return $returnData;
    }
}
?>
