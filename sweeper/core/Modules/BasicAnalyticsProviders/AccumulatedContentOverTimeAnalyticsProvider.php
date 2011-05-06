<?php
namespace Swiftriver\AnalyticsProviders;
include_once(\dirname(__FILE__)."/BaseAnalyticsClass.php");
class AccumulatedContentOverTimeAnalyticsProvider
    extends BaseAnalyticsClass
    implements \Swiftriver\Core\Analytics\IAnalyticsProvider
{
    /**
     * Function that should return the name of the
     * given AnalyticsProvider.
     *
     * @return string
     */
    public function ProviderType()
    {
        return "AccumulatedContentOverTimeAnalyticsProvider";
    }

    /**
     * Function that when implemented by a derived
     * class should return an object that can be
     * json encoded and returned to the UI to
     * provide analytical information about the
     * underlying data.
     *
     * @param \Swiftriver\Core\Analytics\AnalyticsRequest $parameters
     * @return \Swiftriver\Core\Analytics\AnalyticsRequest
     */
    public function ProvideAnalytics($request)
    {
        $logger = \Swiftriver\Core\Setup::GetLogger();

        $logger->log("Swiftriver::AnalyticsProviders::AccumulatedContentOverTimeAnalyticsProvider::ProvideAnalytics [Method Invoked]", \PEAR_LOG_DEBUG);

        $sql =
            "SELECT
                c.date as date,
                count(c.id) as numberofcontentitems
            FROM
                SC_Content c
            GROUP BY
                DAYOFYEAR(FROM_UNIXTIME(c.date))
            ORDER BY
                c.date ASC";

        try
        {
            $db = parent::PDOConnection($request);

            if($db == null)
                return $request;

            $statement = $db->prepare($sql);

            $result = $statement->execute();

            if($result == false)
            {
                $logger->log("Swiftriver::AnalyticsProviders::AccumulatedContentOverTimeAnalyticsProvider::ProvideAnalytics [An exception was thrown]", \PEAR_LOG_ERR);

                $errorCollection = $statement->errorInfo();

                $logger->log("Swiftriver::AnalyticsProviders::AccumulatedContentOverTimeAnalyticsProvider::ProvideAnalytics [" . $errorCollection[2] . "]", \PEAR_LOG_ERR);

                return $request;
            }

            $request->Result = array();

            $accumulatedTotal = 0;
            $lastdate = null;

            foreach($statement->fetchAll() as $row)
            {
                $date = date("d-m-Y", $row['date']);
                
                if($lastdate != null)
                {
                    while($date != date("d-m-Y", \strtotime("$lastdate +1 day")) && $lastdate < date("d-m-Y"))
                    {
                        $entry = array
                        (
                            "date" => $lastdate,
                            "accumulatedtotal" => $accumulatedTotal,
                        );

                        $request->Result[] = $entry;

                        $lastdate = date('d-m-Y', \strtotime("$lastdate +1 day"));
                    }
                }

                $accumulatedTotal += $row["numberofcontentitems"];

                $entry = array
                (
                    "date" => $date,
                    "accumulatedtotal" => $accumulatedTotal,
                );

                $request->Result[] = $entry;

                $lastdate = $date;
                
                
            }
        }
        catch(\PDOException $e)
        {
            $logger->log("Swiftriver::AnalyticsProviders::AccumulatedContentOverTimeAnalyticsProvider::ProvideAnalytics [An exception was thrown]", \PEAR_LOG_ERR);

            $logger->log("Swiftriver::AnalyticsProviders::AccumulatedContentOverTimeAnalyticsProvider::ProvideAnalytics [$e]", \PEAR_LOG_ERR);
        }

        $logger->log("Swiftriver::AnalyticsProviders::AccumulatedContentOverTimeAnalyticsProvider::ProvideAnalytics [Method finished]", \PEAR_LOG_DEBUG);

        return $request;
    }

    /**
     * Function that returns an array containing the
     * fully qualified types of the data content's
     * that the deriving Analytics Provider can work
     * against
     *
     * @return string[]
     */
    public function DataContentSet()
    {
        return array("\\Swiftriver\\Core\\Modules\\DataContext\\MySql_V2\\DataContext");
    }
}
?>
