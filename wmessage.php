<head><script src='https://www.google.com/recaptcha/api.js'></script></head>
<body>
<?php 
// Only edit this part!
$privatekey = "KEY";   //google ReCaptcha secret key
$host = "HOST";                                 // MySQL Host
$user = "USER";                                  // MySQL Username
$password = "PASSWORD";                                   // MySQL Password
$database = "DATABASE";                              // MySQL Database name
// Don't edit anything below this line
class message {
    public $messageID;
    public $email;
    public $time;
    public $author;
    public $title;
    public $message;
    public $origin;
    public $sendtime;
    public $visible;
    function __construct($email, $author, $title, $message, $origin){
        $this->email = $email;
        $this->author = $author;
        $this->title = $title;
        $this->message = $message;
        $this->origin = $origin;
    }
}
class messageDisplay extends message {
    function __construct($messageID, $email, $author, $title, $message, $origin, $sendtime, $visible){
        $this->email = $email;
        $this->author = $author;
        $this->title = $title;
        $this->message = $message;
        $this->origin = $origin;
        $this->sendtime = $sendtime;
        $this->messageID = $messageID;
        $this->visible = $visible;
    }
}

$mysqli = new mysqli($host, $user, $password, $database);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
}
$result = $mysqli->query("SHOW TABLES LIKE 'messages'");
$tableExists = $result->num_rows > 0;
if(!$tableExists){
    echo "Table messages doesn't exist yet, creating it... ";
    $query = "CREATE TABLE messages (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    author VARCHAR(60) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    email VARCHAR(50),
    origin VARCHAR(200),
    visible BOOL DEFAULT 1,
    time TIMESTAMP
    )";
    $mysqli->query($query);
    $result = $mysqli->query("SHOW TABLES LIKE 'messages'");
    $tableExists = $result->num_rows > 0;
    if($tableExists){
        echo "Table created";
    } else {
        echo "Table creation failed!";
    }
}


if(isset($_POST['author'])){
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $response = file_get_contents($url."?secret=".$privatekey."&response=".$_POST['g-recaptcha-response']."&remoteip=".$_SERVER['REMOTE_ADDR']);
    $data = json_decode($response);

    if (isset($data->success) AND $data->success==true) {
            $author = $mysqli->real_escape_string(htmlspecialchars($_POST['author']));
            $message = $mysqli->real_escape_string(htmlspecialchars($_POST['message']));
            $title = $mysqli->real_escape_string(htmlspecialchars($_POST['title']));
            $email = $mysqli->real_escape_string(htmlspecialchars($_POST['email']));
            $origin = $mysqli->real_escape_string(htmlspecialchars($_POST['origin']));
            $message = new message($email, $author, $title, $message, $origin);
            $query = "INSERT INTO messages (email, author, title, message, origin) VALUES ('" . $message->email . "', '" . $message->author . "', '" . $message->title . "', '" . $message->message . "', '" . $message->origin . "')";
            $mysqli->query($query);
    } else {
            echo "Sorry, captcha failed.";
    }
}
$query = "SELECT id FROM messages ORDER BY id DESC LIMIT 1";
$result = $mysqli->query($query);
$row = $result->fetch_array();
$lastID = $row['id'];
$currentID = 1;
while($currentID <= $lastID){
    $query = "SELECT * FROM messages WHERE id = " . $currentID;
    $result = $mysqli->query($query);
    $row = $result->fetch_array();
    $message = new messageDisplay($row['id'], $row['email'], $row['author'], $row['title'], $row['message'], $row['origin'], $row['time'], $row['visible']);
    if($message->visible == 1){
    echo "<div class='message'><h3 class='messageTitle'>" . $message->title . "</h3><p class='messageInfo'>By " . $message->author . " sent at " . $message->sendtime . "</p><br><p class='message'>" . $message->message . "</p><hr></div>";
    } else {
        echo "<div class='message'><h3 class='messageTitle removed'>We're sorry, but this message has been removed</h3><hr></div>";
    }
    ++$currentID;
}

$mysqli->close();
?>


<form action="" method="post">
Name: <input type="text" name="author"><br>
Title: <input type="text" name="title"><br>
Message: <input type="text" name="message"><br>
E-mail: <input type="text" name="email"><br>
<input value="<?php 
$origin = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
echo $origin;
?>" type="hidden" name="origin">
<div class="g-recaptcha" data-sitekey="6LdQSAcTAAAAAP_F5Pd2-UVozLO5sd1g_WATjc0h"></div>
<input type="submit">
</form>
</body>
</html>
