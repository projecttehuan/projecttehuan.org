<?php
namespace Swiftriver\Core\DAL\Repositories;
/**
 * The Repository for generic queries
 * @author mg[at]swiftly[dot]org
 */
class GenericQueryRepository
{
    /**
     * This function should allow calling classes to run basic sql select
     * statements and switch the implmentation based on the data content
     * type.
     * 
     * @param string $sql
     * @return array["results","errors"]
     */
    public function RunGenericQuery($sql)
    {
        $dataContext = \Swiftriver\Core\Setup::DALConfiguration()->DataContextType;

        switch($dataContext)
        {
            case "\Swiftriver\Core\Modules\DataContext\MySql_V2\DataContext":
            {
                $db = \Swiftriver\Core\Modules\DataContext\MySql_V2\DataContext::PDOConnection();
                $statement = $db->prepare($sql);
                $result = $statement->execute();
                return ($result == false)
                    ? array("results" => array(), "errors" => $statement->errorInfo())
                    : array("results" => $statement->fetchAll(), "errors" => null);
            }
        }
    }
}
?>
