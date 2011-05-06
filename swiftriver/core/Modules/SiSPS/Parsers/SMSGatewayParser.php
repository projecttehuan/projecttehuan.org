<?php
namespace Swiftriver\Core\Modules\SiSPS\Parsers;
class SMSGatewayParser implements IParser
{
    /**
     * Implementation of IParser::GetAndParse
     * @param \Swiftriver\Core\ObjectModel\Channel $channel
     * @param datetime $lassucess
     */
    public function GetAndParse($channel)
    {
        $logger = \Swiftriver\Core\Setup::GetLogger();
        $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::GetAndParse [Method invoked]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::GetAndParse [END: Extracting required parameters]", \PEAR_LOG_DEBUG);

        //Create the Content array
        $contentItems = array();

        $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::GetAndParse [START: Parsing SMS items from Remote API]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::GetAndParse [START: Extracting required parameters]", \PEAR_LOG_DEBUG);

        //Extract the Server URL
        $serverURL = $channel->parameters["ServerURL"];
        $serverURL = rtrim($serverURL, ",");

        if(!isset($serverURL)) {
            $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::GetAndParse [the parameter 'ServerURL' was not supplied. Returning null]", \PEAR_LOG_DEBUG);
            $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::GetAndParse [Method finished]", \PEAR_LOG_DEBUG);
            return null;
        }

        $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::GetAndParse [START: Attempting to communicate to gateway server]", \PEAR_LOG_DEBUG);

        $contentItems = $this->getGatewayContentItems($channel, $logger, $serverURL);

        $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::GetAndParse [END: Attempting to communicate to gateway server]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::GetAndParse [END: Parsing SMS items from Remote API]", \PEAR_LOG_DEBUG);

        //return the content array
        return $contentItems;
    }

    private function getGatewayContentItems($channel, $logger, $serverURL) {
        $contentItems = array();

        $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::getRemoteContentItems [Preparing URL]", \PEAR_LOG_DEBUG);

        $endServerURL = "/index.php/welcome/getsms/";

        if(isset($channel->lastSucess)) {
            $endServerURL.=$channel->lastSucess;
        }
        else {
            $endServerURL.="0";
        }

        $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::getRemoteContentItems [Connecting to server: ".$serverURL.$endServerURL."]", \PEAR_LOG_DEBUG);

        $json_response = file_get_contents($serverURL.$endServerURL);
        $json_decoded = json_decode($json_response);

        $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::getRemoteContentItems [Extracting message]", \PEAR_LOG_DEBUG);

        if(is_array($json_decoded->result)) {
            $num_messages = count($json_decoded->result);

            $json_decoded = $json_decoded->result;

            $logger->log("Core::Modules::SiSPS::Parsers::SMSGatewayParser::getRemoteContentItems [Processing $num_messages messages]", \PEAR_LOG_DEBUG);

            for($message_index = 0; $message_index < $num_messages; $message_index ++) {
                $source_name = "";

                // Embed source name and source number
                $source_name = $json_decoded[$message_index]->message_from;

                $source = \Swiftriver\Core\ObjectModel\ObjectFactories\SourceFactory::CreateSourceFromIdentifier($source_name, $channel->trusted);

                $source->name = $source_name;
                $source->parent = $channel->id;
                $source->type = $channel->type;
                $source->subType = $channel->subType;

                //Create a new Content item
                $item = \Swiftriver\Core\ObjectModel\ObjectFactories\ContentFactory::CreateContent($source);

                $item->text[] = new \Swiftriver\Core\ObjectModel\LanguageSpecificText(
                        "", //here we set null as we dont know the language yet
                        "", //the keyword can be used as a subject
                        array($json_decoded[$message_index]->message_text, null)); //the message

                $item->link = null;
                $item->date = time();

                $contentItems[] = $item;
            }

            return $contentItems;
        }
        else {
            return null;
        }

    }


    /**
     * This method returns a string array with the names of all
     * the source types this parser is designed to parse. For example
     * the EventfulParser may return array("Blogs", "News Feeds");
     *
     * @return string[]
     */
    public function ListSubTypes()
    {
        return array("SMS");
    }

    /**
     * This method returns a string describing the type of sources
     * it can parse. For example, the EventfulParser returns "Feeds".
     *
     * @return string type of sources parsed
     */
    public function ReturnType()
    {
        return "SMSGateway";
    }

    /**
     * This method returns an array of the required parameters that
     * are necessary to run this parser. The Array should be in the
     * following format:
     * array(
     *  "SubType" => array ( ConfigurationElements )
     * )
     *
     * @return array()
     */
    public function ReturnRequiredParameters()
    {
        $return = array("SMS" => array(new \Swiftriver\Core\ObjectModel\ConfigurationElement(
                    "ServerURL",
                    "string",
                    "Address of the SMS Gateway location")));
        
        return $return;
    }
}
?>