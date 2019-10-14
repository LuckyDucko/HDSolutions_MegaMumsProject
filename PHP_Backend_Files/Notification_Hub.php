<?php
require_once('DBConnection.php');
class NotificationHub 
{
    const API_VERSION = "?api-version=2013-10";

    private $endpoint;
    private $hubPath;
    private $sasKeyName;
    private $sasKeyValue;

    function __construct($connectionString, $hubPath) 
    {
        $this->hubPath = $hubPath;

        $this->parseConnectionString($connectionString);
    }
    private function parseConnectionString($connectionString) 
    {
        $parts = explode(";", $connectionString);
        if (sizeof($parts) != 3) 
        {
            throw new Exception("Error parsing connection string: " . $connectionString);
        }

        foreach ($parts as $part) 
        {
            if (strpos($part, "Endpoint") === 0) 
            {
                $this->endpoint = "https" . substr($part, 11);
            } 
            else if(strpos($part, "SharedAccessKeyName") === 0) 
            {
                $this->sasKeyName = substr($part, 20);
            } 
            else if (strpos($part, "SharedAccessKey") === 0) 
            {
                $this->sasKeyValue = substr($part, 16);
            }
        }
    }
    private function generateSasToken($uri) 
    {
        $targetUri = strtolower(rawurlencode(strtolower($uri)));

        $expires = time();
        $expiresInMins = 60;
        $expires = $expires + $expiresInMins * 60;
        $toSign = $targetUri . "\n" . $expires;

        $signature = rawurlencode(base64_encode(hash_hmac('sha256', $toSign, $this->sasKeyValue, TRUE)));

        $token = "SharedAccessSignature sr=" . $targetUri . "&sig="
                    . $signature . "&se=" . $expires . "&skn=" . $this->sasKeyName;

        return $token;
    }


    
    public function sendNotification($notification, $tagsOrTagExpression) 
    {
        
        if (is_array($tagsOrTagExpression)) 
        {
            $tagExpression = implode(" || ", $tagsOrTagExpression);
        } 
        else 
        {
            $tagExpression = $tagsOrTagExpression;
        }

        # build uri
        $uri = $this->endpoint . $this->hubPath . "/messages" . NotificationHub::API_VERSION;
        $ch = curl_init($uri);

        if (in_array($notification->format, ["template", "apple", "gcm"])) 
        {
            $contentType = "application/json";
        } 
        else 
        {
            $contentType = "application/xml";
        }

        $token = $this->generateSasToken($uri);

        $headers = 
        [
        'Authorization: '.$token,
        'Content-Type: '.$contentType,
        //'ServiceBusNotification-Tags: '.$userTag,
        'ServiceBusNotification-Format: '.$notification->format
        ];

        if ("" !== $tagExpression) 
        {
            $headers[] = 'ServiceBusNotification-Tags: '.$tagExpression;
        }

        # add headers for other platforms
        if (is_array($notification->headers)) 
        {
            $headers = array_merge($headers, $notification->headers);
        }

        curl_setopt_array($ch, array(
                CURLOPT_POST => TRUE,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $notification->payload
            )
        );

        // Send the request
        $response = curl_exec($ch);

        // Check for errors
        if($response === FALSE)
        {
            throw new Exception(curl_error($ch));
        }

        $info = curl_getinfo($ch);

        if ($info['http_code'] <> 201) 
        {
            throw new Exception('Error sending notificaiton: '. $info['http_code'] . ' msg: ' . $response);
        }
    } 
}

class Notification 
{
    public $format;
    public $payload;

    # array with keynames for headers
    # Note: Some headers are mandatory: Windows: X-WNS-Type, WindowsPhone: X-NotificationType
    # Note: For Apple you can set Expiry with header: ServiceBusNotification-ApnsExpiry in W3C DTF, YYYY-MM-DDThh:mmTZD (for example, 1997-07-16T19:20+01:00).
    /*
        What i need to do, is add in information concerning tags
        The tags is what uniquely identifies a device to a user
        We need to add a storage method of some sort to utilise this tag
        method, otherwise all we are gonna get is globals. 


    */
    public $headers;

    function __construct($format, $payload) 
    {
        if (!in_array($format, ["template", "apple", "windows", "gcm", "windowsphone"])) 
        {
            throw new Exception('Invalid format: ' . $format);
        }
        $this->format = $format;
        $this->payload = $payload;
    }
}


class MegaNotification
{
    private $BaseMessage;
    private $NotificationLevel;

    function __construct($BaseMessage, $NotificationLevel) 
    {
        $this->BaseMessage = $BaseMessage;
        $this->NotificationLevel = $NotificationLevel;
    }
    function AddNotificationToDatabase($Classid)
    {
        try
        {
            
            $NotificationDBConstructor = new MegaDatabaseConnection;
            $NotificationDB = $NotificationDBConstructor->connection();

            $affectedUsers = $NotificationDB->prepare("SELECT USER_CLASSES.USER_ID, USER_CLASSES.CLASS_ID,USER_PROFILE.PROFILE_NOTIFICATION_LEVEL FROM USER_CLASSES INNER JOIN USER_PROFILE ON USER_CLASSES.USER_ID = USER_PROFILE.USER_ID WHERE USER_CLASSES.CLASS_ID = ? AND USER_PROFILE.PROFILE_NOTIFICATION_LEVEL LIKE ?");
            $NotifCheck = '%'.$this->NotificationLevel.'%';
            $affectedUsers->bind_param('is', $Classid, $NotifCheck);

            if($affectedUsers->execute())
            {
                
                $result = $affectedUsers->get_result();
                if($result->num_rows)
                {
                    while($users = $result->fetch_assoc())
                    {
                        $userNotifications = $NotificationDB->prepare("INSERT INTO NOTIFICATIONS (USER_ID,CLASS_ID, MESSAGE, NOTIFICATION_LEVEL) VALUES (?,?,?,?)");
                        $userNotifications->bind_param('iiss', $users['USER_ID'], $Classid,$this->BaseMessage, $this->NotificationLevel);
                        $userNotifications->execute();
                    }
                }
                
            }
            $affectedUsers->close();

        }
        catch (Exception $e)
        {
            return $e;
        }
        return "success";
    }
    function PushUserNotificationTags($userID, $classID)
    {
        try
        {
            $tag = $this->NotificationLevel . $classID;
            $NotificationDBConstructor = new MegaDatabaseConnection;
            $NotificationDB = $NotificationDBConstructor->connection();

            $pushNotifications = $NotificationDB->prepare("INSERT INTO NOTIFICATION_TAGS (USER_ID,NOTIFICATION_TAG, CLASSIFICATION) VALUES (?,?,?)");
            $pushNotifications->bind_param('iss', $userID, $tag, $this->NotificationLevel);
            $pushNotifications->execute();
            $pushNotifications->close();
        }
        catch (Exception $e)
        {
            return $e;
        } 
        return "success";
    }

    function SendRemoteNotificationAzure($classID)
    {
        $tag = $this->NotificationLevel . $classID;
        $hub = new NotificationHub("Endpoint=sb://meganotification.servicebus.windows.net/;SharedAccessKeyName=DefaultFullSharedAccessSignature;SharedAccessKey=VFTwjLEvM5ysajLNMaVihBsy3fznciBr5OOn36mbvI0=", "meganotification"); 
        
        $GCMmessage = '{"data":{"msg":"'.$this->BaseMessage.'"}}';
        $iOSmessage = '{"aps":{"alert":"'.$this->BaseMessage.'"}}'; 
        try
        {
            $GCMnotification = new Notification("gcm", $GCMmessage);
            $iOSnotification = new Notification("apple", $iOSmessage);
            $hub->sendNotification($GCMnotification, $tag);
            $hub->sendNotification($iOSnotification, $tag);
        }
        catch (Exception $e)
        {
            return $e;
        }
        return "success";
    }
}

?>

