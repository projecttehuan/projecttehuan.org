<?php
namespace Swiftriver\Core\Modules\SiSPS\Parsers;
class FeedsParser implements IParser
{
    /**
     * Implementation of IParser::GetAndParse
     * @param \Swiftriver\Core\ObjectModel\Channel $channel
     * @param datetime $lassucess
     */
    public function GetAndParse($channel)
    {
        $logger = \Swiftriver\Core\Setup::GetLogger();
        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetAndParse [Method invoked]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetAndParse [START: Extracting required parameters]", \PEAR_LOG_DEBUG);

        //Extract the required variables
        $feedUrl = "";

        switch($channel->subType) {
            case "Wordpress Blog":
                $feedUrl = $channel->parameters["BlogUrl"];
                $feedUrl = rtrim($feedUrl, "/")."/?feed=atom";
            break;

            case "Blogger Blog":
                $feedUrl = $channel->parameters["BlogUrl"];
                $feedUrl = rtrim($feedUrl, "/")."/feeds/posts/default";
            break;

            case "Tumblr Blog":
                $feedUrl = $channel->parameters["BlogUrl"];
                $feedUrl = rtrim($feedUrl, "/")."/api/read";
            break;

            case "News Feeds":
                $feedUrl = $channel->parameters["feedUrl"];
            break;
        }


        if(!isset($feedUrl) || ($feedUrl == "")) {
            $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetAndParse [the parameter 'feedUrl' was not supplied. Returning null]", \PEAR_LOG_DEBUG);
            $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetAndParse [Method finished]", \PEAR_LOG_DEBUG);
            return null;
        }

        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetAndParse [END: Extracting required parameters]", \PEAR_LOG_DEBUG);

        //Include the Simple Pie Framework to get and parse feeds
        $config = \Swiftriver\Core\Setup::Configuration();

        $contentItems = null;

        switch($channel->subType) {
            case "Wordpress Blog":
                $contentItems = $this->GetSimplePieContentEntries($channel, $config, $logger, $feedUrl);
            break;

            case "Blogger Blog":
                $contentItems = $this->GetSimplePieContentEntries($channel, $config, $logger, $feedUrl);
            break;

            case "Tumblr Blog":
                $contentItems = $this->GetXMLContentEntries($channel, $config, $logger, $feedUrl);
            break;

            case "News Feeds":
                $contentItems = $this->GetSimplePieContentEntries($channel, $config, $logger, $feedUrl);
            break;
        }

        //return the content array
        return $contentItems;
    }

    private function GetXMLContentEntries($channel, $config, $logger, $feedUrl)
    {
        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetXMLContentEntries [Getting the XML from the API]", \PEAR_LOG_DEBUG);

        $simple_xml_document = simplexml_load_string(file_get_contents($feedUrl));

        $contentItems = array();

        $lastSuccess = intval($channel->lastSuccess);

        foreach($simple_xml_document->posts->post as $simple_xml_post) {
            $contentdate = intval($simple_xml_post["unix-timestamp"]);

            if(isset($lastSuccess) && is_numeric($lastSuccess) && isset($contentdate) && is_numeric($contentdate)) {
                $lastSuccessGMTime = strtotime(gmdate("M d Y H:i:s", $lastSuccess));

                if($contentdate < $lastSuccessGMTime) {
                    $textContentDate = date("c", $contentdate);
                    $textlastSuccess = date("c", $lastSuccess);
                    $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [Skipped feed item as date $textContentDate less than last sucessful run ($textlastSuccess)]", \PEAR_LOG_DEBUG);

                    continue;
                }
            }
            
            //Get source data
            $source_name = "Tumblr";
            $source_name = ($source_name == null || $source_name == "") ? $feedUrl : $source_name . " @ " . $feedUrl;
            $source = \Swiftriver\Core\ObjectModel\ObjectFactories\SourceFactory::CreateSourceFromIdentifier($source_name, $channel->trusted);
            $source->name = $source_name;
            $source->parent = $channel->id;
            $source->type = $channel->type;
            $source->subType = $channel->subType;

            //Extract all the relevant feedItem info
            $array_post = (array) $simple_xml_post;
            $title = isset($array_post["regular-title"]) ? $array_post["regular-title"] : $simple_xml_post["slug"];
            $description = isset($array_post["regular-body"]) ? $array_post["regular-body"] : $array_post["photo-caption"];
            $contentLink = $simple_xml_post["url"];

            //Create a new Content item
            $item = \Swiftriver\Core\ObjectModel\ObjectFactories\ContentFactory::CreateContent($source);

            //Fill the Content Item
            $item->text[] = new \Swiftriver\Core\ObjectModel\LanguageSpecificText(
                    null, //here we set null as we dont know the language yet
                    $title,
                    array($description));
            $item->link = $contentLink;
            $item->date = $contentdate;

            //Add the item to the Content array
            $contentItems[] = $item;
        }

        return $contentItems;
    }

    private function GetSimplePieContentEntries($channel, $config, $logger, $feedUrl)
    {
        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [START: Including the SimplePie module]", \PEAR_LOG_DEBUG);
        
        $simplePiePath = $config->ModulesDirectory."/SimplePie/simplepie.inc";
        include_once($simplePiePath);

        //Include the Simple Pie YouTube Framework
        $simpleTubePiePath = $config->ModulesDirectory."/SimplePie/simpletube.inc";
        include_once($simpleTubePiePath);

        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [END: Including the SimplePie module]", \PEAR_LOG_DEBUG);

        //Construct a new SimplePie Parser
        $feed = new \SimplePie();

        //Get the cache directory
        $cacheDirectory = $config->CachingDirectory;

        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [Setting the caching directory to $cacheDirectory]", \PEAR_LOG_DEBUG);

        //Set the caching directory
        $feed->set_cache_location($cacheDirectory);

        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [Setting the feed url to $feedUrl]", \PEAR_LOG_DEBUG);

        //Pass the feed URL to the SImplePie object
        $feed->set_feed_url($feedUrl);

        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [Initializing the feed]", \PEAR_LOG_DEBUG);

        //Run the SimplePie
        $feed->init();

        //Strip HTML
        $feed->strip_htmltags(array('span', 'font', 'style'));

        //Create the Content array
        $contentItems = array();

        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [START: Parsing feed items]", \PEAR_LOG_DEBUG);

        $feeditems = $feed->get_items();

        if(!$feeditems || $feeditems == null || !is_array($feeditems) || count($feeditems) < 1) {
            $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [No feeditems recovered from the feed]", \PEAR_LOG_DEBUG);
        }

        $lastSuccess = $channel->lastSuccess;

        //Loop through the Feed Items
        foreach($feeditems as $feedItem)
        {

            //Extract the date of the content
            $contentdate =  strtotime($feedItem->get_date());
            if(isset($lastSuccess) && is_numeric($lastSuccess) && isset($contentdate) && is_numeric($contentdate)) {
                if($contentdate < $lastSuccess) {
                    $textContentDate = date("c", $contentdate);
                    $textlastSuccess = date("c", $lastSuccess);
                    $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [Skipped feed item as date $textContentDate less than last sucessful run ($textlastSuccess)]", \PEAR_LOG_DEBUG);
                    continue;
                }
            }

            $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [Adding feed item]", \PEAR_LOG_DEBUG);

            //Get source data
            $source_name = $feedItem->get_author()->name;
            $source_name = ($source_name == null || $source_name == "") ? $feedUrl : $source_name . " @ " . $feedUrl;
            $source = \Swiftriver\Core\ObjectModel\ObjectFactories\SourceFactory::CreateSourceFromIdentifier($source_name, $channel->trusted);
            $source->name = $source_name;
            $source->email = $feedItem->get_author()->email;
            $source->parent = $channel->id;
            $source->type = $channel->type;
            $source->subType = $channel->subType;


            //Extract all the relevant feedItem info
            $title = $feedItem->get_title();
            $description = $feedItem->get_description();
            $contentLink = $feedItem->get_permalink();
            $date = $feedItem->get_date();

            //Create a new Content item
            $item = \Swiftriver\Core\ObjectModel\ObjectFactories\ContentFactory::CreateContent($source);

            //Fill the Content Item
            $item->text[] = new \Swiftriver\Core\ObjectModel\LanguageSpecificText(
                    null, //here we set null as we dont know the language yet
                    $title,
                    array($description));
            $item->link = $contentLink;
            $item->date = strtotime($date);

            //Add the item to the Content array
            $contentItems[] = $item;
        }

        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [END: Parsing feed items]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::Parsers::FeedsParser::GetSimplePieContentEntries [Method finished]", \PEAR_LOG_DEBUG);

        //return the content array
        return $contentItems;
    }

    /**
     * This method returns a string array with the names of all
     * the source types this parser is designed to parse. For example
     * the FeedsParser may return array("Blogs", "News Feeds");
     *
     * @return string[]
     */
    public function ListSubTypes()
    {
        return array(
            "Wordpress Blog",
            "Blogger Blog",
            "Tumblr Blog",
            "News Feeds"
        );
    }

    /**
     * This method returns a string describing the type of sources
     * it can parse. For example, the FeedsParser returns "Feeds".
     *
     * @return string type of sources parsed
     */
    public function ReturnType()
    {
        return "Feeds";
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
        return array(
            "Wordpress Blog" => array (
                new \Swiftriver\Core\ObjectModel\ConfigurationElement(
                        "BlogUrl",
                        "string",
                        "The URL of the blog (must include 'http://')"
                )
            ),
            "Blogger Blog" => array(
                new \Swiftriver\Core\ObjectModel\ConfigurationElement(
                        "BlogUrl",
                        "string",
                        "The URL of the blog (must include 'http://')"
                )
            ),
            "Tumblr Blog" => array(
                new \Swiftriver\Core\ObjectModel\ConfigurationElement(
                        "BlogUrl",
                        "string",
                        "The URL of the blog (must include 'http://')"
                )
            ),
            "News Feeds" => array(
                new \Swiftriver\Core\ObjectModel\ConfigurationElement(
                        "feedUrl",
                        "string",
                        "The URL of the feed (must include 'http://')"
                )
            )
        );
    }
}
?>