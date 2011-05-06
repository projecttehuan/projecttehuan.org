<?php
namespace Swiftriver\EventHandlers;
include_once(dirname(__FILE__)."/ContentToUshahidiAPIParser.php");
include_once(dirname(__FILE__)."/ServiceInterface.php");
class UshahidiContentPushEventHandler implements \Swiftriver\Core\EventDistribution\IEventHandler {
    /**
     * This method should return the name of the event handler
     * that you implement. This name should be unique across all
     * event handlers and should be no more that 50 chars long
     *
     * @return string
     */
    public function Name() {
        return "Ushahidi Content Push";
    }

    /**
     * This method should return a description describing what
     * exactly it is that your Event Handler does
     *
     * @return string
     */
    public function Description() {
        return "Activating this plugin causes all content that has been " .
               "processed to be sent to the associated Ushahidi instance " .
               "as a new report.";
    }

    /**
     * This method returns an array of the required paramters that
     * are nessesary to configure this event handler.
     *
     * @return \Swiftriver\Core\ObjectModel\ConfigurationElement[]
     */
    public function ReturnRequiredParameters(){
        return array(
            new \Swiftriver\Core\ObjectModel\ConfigurationElement(
                    "Ushahidi Url",
                    "string",
                    "The fully qualified url to the Ushahidi instance you want to " .
                    "communicate with (please dont include the API path, just the root)"),
        );
    }

    /**
     * This method should return the names of the events
     * that your EventHandler wishes to subscribe to.
     *
     * @return string[]
     */
    public function ReturnEventNamesToHandle() {
        return array(
            \Swiftriver\Core\EventDistribution\EventEnumeration::$ContentPostProcessing,
        );
    }

    /**
     * Given a GenericEvent object, this method should do
     * something amazing with the data contained in the
     * event arguments.
     *
     * @param GenericEvent $event
     * @param \Swiftriver\Core\Configuration\ConfigurationHandlers\CoreConfigurationHandler $configuration
     * @param \Log $logger
     */
    public function HandleEvent($event, $configuration, $logger) {
        $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [Method invoked]", \PEAR_LOG_DEBUG);

        //Get the $event->arguments as a content item
        $content = $event->arguments;

        //get the module configuraiton
        $config = \Swiftriver\Core\Setup::DynamicModuleConfiguration()->Configuration;

        if(!key_exists($this->Name(), $config)) {
            $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [The Ushahidi Event Handler was called but no configuration exists for this module]", \PEAR_LOG_ERR);
            $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [Method finished]", \PEAR_LOG_DEBUG);
            return;
        }

        $config = $config[$this->Name()];

        foreach($this->ReturnRequiredParameters() as $requiredParam) {
            if(!key_exists($requiredParam->name, $config)) {
                $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [The Ushahidi Event Handler was called but all the required configuration properties could not be loaded]", \PEAR_LOG_ERR);
                $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [Method finished]", \PEAR_LOG_DEBUG);
                return;
            }
        }

        //extract the Url for Ushahidi
        $uri = (string) $config["Ushahidi Url"]->value;
        $uri = rtrim($uri, "/")."/api";

        //null check the uri
        if($uri == null || $uri == "") {
            $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [The Ushahidi Event Handler was called but all the required configuration properties could not be loaded]", \PEAR_LOG_ERR);
            $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [Method finished]", \PEAR_LOG_DEBUG);
            return;
        }

        $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [Pushing trusted content item to Ushahidi]", \PEAR_LOG_DEBUG);

        foreach($content as $item) {
            // Trusted content
            //Instanciate the parser that will be used to parse the content item into Ushahidi format
            $toUshahidiParser = new \Swiftriver\UshahidiContentPush\ContentToUshahidiAPIParser();

            //Get the ushahidi formatted params from the parser
            $parameters = $toUshahidiParser->ParseContentItemToUshahidiAPIFormat($item);

            //include the service wrapper
            $service = new \Swiftriver\UshahidiContentPush\ServiceInterface();

            $json_returned = "";

            try {
                //Call the service and get the return
                $serviceReturn = $service->InterafceWithService($uri, $parameters, $configuration);

                //null check return
                if($serviceReturn == null || !is_string($serviceReturn))
                    throw new \Exception("The service returned null or a none string");

                //try to decode the json from the service
                $json_returned = $serviceReturn;
                $json = json_decode($serviceReturn);

                //Check that there is valid JSON
                if(!$json) {
                    throw new \Exception("The service returned a none json string");
                }

                if(!property_exists($json, "success")) {
                    throw new \Exception("The service returned JSON but it did not contain the property 'success'");
                }

                if($json->success === false) {
                    throw new \Exception("The service returned a false in the success flag");
                }
            }
            catch (\Exception $e) {
                $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [An exception was thrown]", \PEAR_LOG_ERR);
                $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [$e]", \PEAR_LOG_ERR);

                //Output the Returned value
                $logger->log("Value returned from service call:".$json_returned, \PEAR_LOG_ERR);

                $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [Method finished]", \PEAR_LOG_DEBUG);
            }
        }

        $logger->log("Swiftriver::EventHandlers::UshahidiContentPushEventHandler::HandleEvent [Method finished]", \PEAR_LOG_DEBUG);
    }
}
?>
