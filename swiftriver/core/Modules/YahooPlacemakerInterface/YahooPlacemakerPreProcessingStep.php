<?php
namespace Swiftriver\PreProcessingSteps;
class YahooPlacemakerPreProcessingStep implements \Swiftriver\Core\PreProcessing\IPreProcessingStep
{
    /**
     * Given a collection of content items,
     *
     * @param \Swiftriver\Core\ObjectModel\Content[] $contentItems
     * @param \Swiftriver\Core\Configuration\ConfigurationHandlers\CoreConfigurationHandler $configuration
     * @param \Log $logger
     * @return \Swiftriver\Core\ObjectModel\Content[]
     */
    public function Process($contentItems, $configuration, $logger) {
        $logger->log("PreProcessingSteps::YahooPlacemakerPreProcessingStep::Process [Method invoked]", \PEAR_LOG_DEBUG);

        //if the content is not valid, jsut return it
        if(!isset($contentItems) || !is_array($contentItems) || count($contentItems) < 1) {
            $logger->log("PreProcessingSteps::YahooPlacemakerPreProcessingStep::Process [No content supplied]", \PEAR_LOG_DEBUG);
            $logger->log("PreProcessingSteps::YahooPlacemakerPreProcessingStep::Process [Method finished]", \PEAR_LOG_DEBUG);
            return $contentItems;
        }

        //get the module configuraiton
        $config = \Swiftriver\Core\Setup::DynamicModuleConfiguration()->Configuration;

        if(!key_exists($this->Name(), $config)) {
            $logger->log("PreProcessingSteps::YahooPlacemakerPreProcessingStep::Process [The Ushahidi Event Handler was called but no configuration exists for this module]", \PEAR_LOG_ERR);
            $logger->log("PreProcessingSteps::YahooPlacemakerPreProcessingStep::Process [Method finished]", \PEAR_LOG_DEBUG);
            return $contentItems;
        }

        $config = $config[$this->Name()];

        foreach($this->ReturnRequiredParameters() as $requiredParam) {
            if(!key_exists($requiredParam->name, $config)) {
                $logger->log("PreProcessingSteps::YahooPlacemakerPreProcessingStep::Process [The Ushahidi Event Handler was called but all the required configuration properties could not be loaded]", \PEAR_LOG_ERR);
                $logger->log("PreProcessingSteps::YahooPlacemakerPreProcessingStep::Process [Method finished]", \PEAR_LOG_DEBUG);
                return $contentItems;
            }
        }

        $appid = (string) $config["Yahoo Placemaker App Id"]->value;

        $logger->log("PreProcessingSteps::YahooPlacemakerPreProcessingStep::Process [START: Looping through content items]", \PEAR_LOG_DEBUG);

        for($i=0; $i < \count($contentItems); $i++)
        {
            $content = $contentItems[$i];

            if(\count($content->gisData) == 0 && \count($content->source->gisData) == 0)
            {
                $text = $content->text[0]->title;

                foreach($content->text[0]->text as $t)
                    $text .= " " . $t;

                $gis = $this->YahooPlacemakerRequest($text, $appid);

                $content->gisData[] = $gis;
            }

            $contentItems[$i] = $content;
        }

        $logger->log("PreProcessingSteps::YahooPlacemakerPreProcessingStep::Process [END: Looping through content items]", \PEAR_LOG_DEBUG);

        $logger->log("PreProcessingSteps::YahooPlacemakerPreProcessingStep::Process [Method finished]", \PEAR_LOG_DEBUG);

        //return the translated content
        return $contentItems;
    }


    private function curl_request($url, $postvars = null)
    {
        $ch = curl_init();
        $timeout = 10; // set to zero for no timeout
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        if($postvars != null)
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
        }

        $file_contents = curl_exec($ch);
        curl_close($ch);
        return $file_contents;
    }

    public function YahooPlacemakerRequest($location, $appid)
    {
        $encodedLocation = \urlencode($location);
        $url = "http://wherein.yahooapis.com/v1/document";
        $postvars = "documentContent=$encodedLocation&documentType=text/plain&appid=$appid";
        $return = $this->curl_request($url, $postvars);
        $xml = new \SimpleXMLElement($return);
        $long = (float) $xml->document->placeDetails->place->centroid->longitude;
        $latt = (float) $xml->document->placeDetails->place->centroid->latitude;
        $gis = new \Swiftriver\Core\ObjectModel\GisData($long, $latt, $location);
        return $gis;
    }

    public function Description() {
        return "Activating this turbine will invoke the Yahoo Placemaker " .
               "service for every piece of content where it, and it's source do not " .
               "carry any geo data. <a href='http://developer.yahoo.com/geo/placemaker/#how' target='_blank' >Click to get your App ID</a>";
    }

    public function Name() {
        return "Geo Location (Yahoo)";
    }

    public function ReturnRequiredParameters() {
        return array(
            new \Swiftriver\Core\ObjectModel\ConfigurationElement(
                    "Yahoo Placemaker App Id",
                    "string",
                    "The app id you are given when you register a new application ".
                    "with the Yahoo Placemaker service. <a href='http://developer.yahoo.com/geo/placemaker/#how' target='_blank' >Click to get your App ID</a>"),
        );
    }
}
?>
