<?php
require_once('XML/Query2XML.php');
require_once('DB.php');
$query2xml = XML_Query2XML::factory(DB::connect('mysql://root@localhost/Query2XML_Tests'));

require_once('Log.php');
$debugLogger = &Log::factory('file', 'case06.log', 'XML_Query2XML');
$query2xml->enableDebugLog($debugLogger);

$query2xml->startProfiling();


$dom = $query2xml->getXML(
    "SELECT
         s.*,
         manager.employeeid AS manager_employeeid,
         manager.employeename AS manager_employeename,
         d.*,
         department_head.employeeid AS department_head_employeeid,
         department_head.employeename AS department_head_employeename,
         e.*,
         sa.*,
         c.*,
         al.*,
         ar.*,
         (SELECT COUNT(*) FROM sale WHERE sale.store_id = s.storeid) AS store_sales,
         (SELECT
            COUNT(*)
          FROM
            sale, employee, employee_department
          WHERE
            sale.employee_id = employee.employeeid
            AND
            employee_department.employee_id = employee.employeeid
            AND
            employee_department.department_id = d.departmentid
         ) AS department_sales,
         (SELECT
            COUNT(*)
          FROM
            employee, employee_department, department
          WHERE
            employee_department.employee_id = employee.employeeid
            AND
            employee_department.department_id = department.departmentid
            AND
            department.store_id = s.storeid
         ) AS store_employees,
         (SELECT
            COUNT(*)
          FROM
            employee, employee_department
          WHERE
            employee_department.employee_id = employee.employeeid
            AND
            employee_department.department_id = d.departmentid
         ) AS department_employees
     FROM
         store s
          LEFT JOIN employee manager ON s.manager = manager.employeeid
         LEFT JOIN department d ON d.store_id = s.storeid
          LEFT JOIN employee department_head ON department_head.employeeid = d.department_head
          LEFT JOIN employee_department ed ON ed.department_id = d.departmentid
           LEFT JOIN employee e ON e.employeeid = ed.employee_id
            LEFT JOIN sale sa ON sa.employee_id = e.employeeid
             LEFT JOIN customer c ON c.customerid = sa.customer_id
             LEFT JOIN album al ON al.albumid = sa.album_id
              LEFT JOIN artist ar ON ar.artistid = al.artist_id",
    array(
        'rootTag' => 'music_company',
        'rowTag' => 'store',
        'idColumn' => 'storeid',
        'attributes' => array(
            'storeid'
        ),
        'elements' => array(
            'store_sales',
            'store_employees',
            'manager' => array(
                'idColumn' => 'manager_employeeid',
                'attributes' => array(
                    'manager_employeeid'
                ),
                'elements' => array(
                    'manager_employeename'
                )
            ),
            'address' => array(
                'elements' => array(
                    'country',
                    'state' => '!return Helper::getStatePostalCode($record["state"]);',
                    'city',
                    'street',
                    'phone'
                )
            ),
            'department' => array(
                'idColumn' => 'departmentid',
                'attributes' => array(
                    'departmentid'
                ),
                'elements' => array(
                    'department_sales',
                    'department_employees',
                    'departmentname',
                    'department_head' => array(
                        'idColumn' => 'department_head_employeeid',
                        'attributes' => array(
                            'department_head_employeeid'
                        ),
                        'elements' => array(
                            'department_head_employeename'
                        )
                    ),
                    'employees' => array(
                        'rootTag' => 'employees',
                        'rowTag' => 'employee',
                        'idColumn' => 'employeeid',
                        'attributes' => array(
                            'employeeid'
                        ),
                        'elements' => array(
                            'employeename',
                            'sales' => array(
                                'rootTag' => 'sales',
                                'rowTag' => 'sale',
                                'idColumn' => 'saleid',
                                'attributes' => array(
                                    'saleid'
                                ),
                                'elements' => array(
                                    'timestamp',
                                    'customer' => array(
                                        'idColumn' => 'customerid',
                                        'attributes' => array(
                                            'customerid'
                                        ),
                                        'elements' => array(
                                            'first_name',
                                            'last_name',
                                            'email'
                                        )
                                    ),
                                    'album' => array(
                                        'idColumn' => 'albumid',
                                        'attributes' => array(
                                            'albumid'
                                        ),
                                        'elements' => array(
                                            'title',
                                            'published_year',
                                            'comment' => '?!return Helper::summarize($record["comment"], 12);',
                                            'artist' => array(
                                                'idColumn' => 'artistid',
                                                'attributes' => array(
                                                    'artistid'
                                                ),
                                                'elements' => array(
                                                    'name',
                                                    'birth_year',
                                                    'birth_place',
                                                    'genre'
                                                )
                                            )
                                        ) // album elements
                                    ) //album array
                                ) //sales elements
                            ) //sales array
                        ) //employees elements
                    ) //employees array
                ) //department elements
            ) // department array
        ) //root elements
    ) //root
); //getXML method call

$root = $dom->firstChild;
$root->setAttribute('date_generated', date("Y-m-d\TH:i:s", 1124801570));

header('Content-Type: application/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

require_once('XML/Beautifier.php');
$beautifier = new XML_Beautifier();
print $beautifier->formatString($dom->saveXML());

require_once('File.php');
$fp = new File();
$fp->write('case06.profile', $query2xml->getProfile(), FILE_MODE_WRITE);


/**Static class that provides validation and parsing methods for
* generating XML.
*
* It is static so that we can easyly call its methods from inside
* Query2XML using eval'd code.
*/
class Helper
{
    /**Associative array of US postal state codes*/
    public static $statePostalCodes = array(
        'ALABAMA' => 'AL', 'ALASKA' => 'AK', 'AMERICAN SAMOA' => 'AS', 'ARIZONA' => 'AZ', 'ARKANSAS' => 'AR', 'CALIFORNIA' => 'CA',
        'COLORADO' => 'CO', 'CONNECTICUT' => 'CT', 'DELAWARE' => 'DE', 'DISTRICT OF COLUMBIA' => 'DC', 'FEDERATED STATES OF MICRONESIA' => 'FM',
        'FLORIDA' => 'FL', 'GEORGIA' => 'GA', 'GUAM' => 'GU', 'HAWAII' => 'HI', 'IDAHO' => 'ID', 'ILLINOIS' => 'IL', 'INDIANA' => 'IN',
        'IOWA' => 'IA', 'KANSAS' => 'KS', 'KENTUCKY' => 'KY', 'LOUISIANA' => 'LA', 'MAINE' => 'ME', 'MARSHALL ISLANDS' => 'MH', 'MARYLAND' => 'MD',
        'MASSACHUSETTS' => 'MA', 'MICHIGAN' => 'MI', 'MINNESOTA' => 'MN', 'MISSISSIPPI' => 'MS', 'MISSOURI' => 'MO', 'MONTANA' => 'MT',
        'NEBRASKA' => 'NE', 'NEVADA' => 'NV', 'NEW HAMPSHIRE' => 'NH', 'NEW JERSEY' => 'NJ', 'NEW JESEY' => 'NJ', 'NEW MEXICO' => 'NM', 'NEW YORK' => 'NY',
        'NORTH CAROLINA' => 'NC', 'NORTH DAKOTA' => 'ND', 'NORTHERN MARIANA ISLANDS' => 'MP', 'OHIO' => 'OH', 'OKLAHOMA' => 'OK', 'OREGON' => 'OR',
        'PALAU' => 'PW', 'PENNSYLVANIA' => 'PA', 'PUERTO RICO' => 'PR', 'RHODE ISLAND' => 'RI', 'SOUTH CAROLINA' => 'SC', 'SOUTH DAKOTA' => 'SD',
        'TENNESSEE' => 'TN', 'TEXAS' => 'TX', 'UTAH' => 'UT', 'VERMONT' => 'VT', 'VIRGIN ISLANDS' => 'VI', 'VIRGINIA' => 'VA', 'WASHINGTON' => 'WA',
        'WEST VIRGINIA' => 'WV', 'WISCONSIN' => 'WI', 'WYOMING' => 'WY'
    );
            
    /**Translates a US state name into its two-letter postal code.
    * If the translation fails, $state is returned unchanged
    * @param $state The state's name
    */
    public static function getStatePostalCode($state)
    {
        $s = str_replace("  ", " ", trim(strtoupper($state)));
        if (isset(self::$statePostalCodes[$s])) {
            return self::$statePostalCodes[$s];
        } else {
            return $state;
        }
    }
      
    function summarize($str, $limit=50, $appendString=' ...')
    {
        if (strlen($str) > $limit) {
            $str = substr($str, 0, $limit - strlen($appendString)) . $appendString;
        }
        return $str;
    }
}
?>