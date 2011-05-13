<?php
namespace Swiftriver\GoogleLanguageServiceInterface;
class TranslationInterface
{
    private $sourceLangCode;
    private $requiredLangCode;
    private $referer;
    private $logger;

    public function __construct($sourceLangCode, $requiredLangCode, $referer, $logger)
    {
        $this->sourceLangCode = $sourceLangCode;
        $this->requiredLangCode = $requiredLangCode;
        $this->referer = $referer;
        $this->logger = $logger;
    }

    public function Translate($text)
    {
        $logger = $this->logger;
        $logger->log("Swiftriver::GoogleLanguageServiceInterface::TranslationInterface::Translate [Method invoked]", \PEAR_LOG_DEBUG);

        try 
        {
            $text = urlencode($text);
            $languagePair = "$this->sourceLangCode%7C$this->requiredLangCode";
            $uri = "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q=$text&langpair=$languagePair";
            $serviceWrapper = new \Swiftriver\Core\Modules\SiSW\ServiceWrapper($uri);
            $returnData = $serviceWrapper->MakeGETRequest(array("Referer: " . $this->referer));
            $object = json_decode($returnData);
            $return = $object->responseData->translatedText;
        }
        catch (\Exception $e)
        {
            $logger->log("Swiftriver::GoogleLanguageServiceInterface::TranslationInterface::Translate [$e]", \PEAR_LOG_DEBUG);
            $logger->log("Swiftriver::GoogleLanguageServiceInterface::TranslationInterface::Translate [Method finished]", \PEAR_LOG_DEBUG);
            return null;
        }

        $logger->log("Swiftriver::GoogleLanguageServiceInterface::TranslationInterface::Translate [Method finished]", \PEAR_LOG_DEBUG);

        return $return;
    }
}
?>
