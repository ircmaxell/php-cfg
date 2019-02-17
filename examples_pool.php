<?php
$a = "a";
$b = $a;



$a = "a";
$a = $a;
echo $a;
$a = $a;



$a = "a";
if(True) {
  $a = "b";
}
echo $a;




$x = 5;
$y = 4;
echo $x + $y;



//***********************************************************************************

$t = date("H");

if ($t < "20") {
    echo "Have a good day!";
} else {
    echo "Have a good night!";
}


//***********************************************************************************
$favcolor = "red";

switch ($favcolor) {
    case "red":
        echo "Your favorite color is red!";
        break;
    case "blue":
        echo "Your favorite color is blue!";
        break;
    case "green":
        echo "Your favorite color is green!";
        break;
    default:
        echo "Your favorite color is neither red, blue, nor green!";
}


//***********************************************************************************
$x = 1; 

while($x <= 5) {
    echo "The number is: $x\n";
    $x++;
} 

//**************************************

$x = 1; 

while($x <= 5) {
    echo "The number is: $x\n";
    $x = $x + 1;
} 
//***********************************************************************************

$x = 1; 

do {
    echo "The number is: $x\n";
    $x++;
} while ($x <= 5);


for ($x = 0; $x <= 10; $x++) {
    echo "The number is: $x\n";
} 

//***********************************************************************************

$colors = array("red", "green", "blue", "yellow"); 

foreach ($colors as $value) {
    echo "$value\n";
}

//***********************************************************************************

function familyName($fname) {
    echo "$fname\n";
}

familyName("Test");



//***********************************************************************************

function familyName($fname, $year) {
    echo "$fname. Born in $year\n";
}

familyName("Sarah", "1975");


//***********************************************************************************
function sum($x, $y) {
    $z = $x + $y;
    return $z;
}

echo "5 + 10 = " . sum(5, 10) . "\n";
echo "7 + 13 = " . sum(7, 13) . "\n";
echo "2 + 4 = " . sum(2, 4);

//***********************************************************************************

$cars = array("Volvo", "BMW", "Toyota");
echo "I like " . $cars[0] . ", " . $cars[1] . " and " . $cars[2] . ".";

//***********************************************************************************

$cars = array("Volvo", "BMW", "Toyota");
$arrlength = count($cars);

for($x = 0; $x < $arrlength; $x++) {
    echo $cars[$x]."\n";
}

//***********************************************************************************
$age = array("Peter"=>"35", "Ben"=>"37", "Joe"=>"43");

foreach($age as $x => $x_value) {
    echo "Key=" . $x . ", Value=" . $x_value;
    echo "\n";
}



//***********************************************************************************

$servername = "localhost";
$username = "username";
$password = "password";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
echo "Connected successfully";

//***********************************************************************************

$servername = "localhost";
$username = "username";
$password = "password";

try {
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "CREATE DATABASE myDBPDO";
    $conn->exec($sql);
    echo "Database created successfully<br>";
    }
catch(PDOException $e)
    {
    echo $sql . "<br>" . $e->getMessage();
    }

$conn = null;

//***********************************************************************************

function checkNum($number) {
  if($number>1) {
    throw new Exception("Value must be 1 or below");
  }
  return true;
}

try {
  checkNum(2);
  echo 'If you see this, the number is 1 or below';
}

catch(Exception $e) {
  echo 'Message: ' .$e->getMessage();
}







//***********************************************************************************

$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "myDB";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$sql = "INSERT INTO MyGuests (firstname, lastname, email)
VALUES ('John', 'Doe', 'john@example.com')";

if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();


//***********************************************************************************

class Test{
    public function A($param) {
        $this->B($param);
    }
    public function B($param){
        $this->C($param);
    }
    public function C($param){
        echo $param;
    }
}
$test = new Test();
$value = "Hello World!";
$test->A($value);
$test->B($value);


//***********************************************************************************
class Test{
    public static function A($param) {
        self::B($param);
    }
    public static function B($param){
        echo $param;
    }
}
$userinput = "Hello World";
Test::A($userinput);
Test::B($userinput);


?>
