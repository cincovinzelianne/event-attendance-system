<?php
require_once 'config/database.php';

class GoogleCalendar {
    private $client;
    private $service;
    
    public function __construct() {
        // You'll need to set up Google Calendar API credentials
        // Download the credentials JSON file and place it in the config folder
        $this->client = new Google_Client();
        $this->client->setApplicationName('Event Attendance System');
        $this->client->setScopes(Google_Service_Calendar::CALENDAR);
        $this->client->setAuthConfig('config/google_credentials.json');
        $this->client->setAccessType('offline');
        
        // Load existing token or get new one
        $tokenPath = 'config/token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($accessToken);
        }
        
        // If there is no previous token or it's expired
        if ($this->client->isAccessTokenExpired()) {
            // Refresh the token if possible, otherwise fetch a new one
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            } else {
                // You'll need to implement OAuth flow for first-time setup
                $authUrl = $this->client->createAuthUrl();
                // Redirect user to $authUrl for authorization
                // After authorization, save the token
            }
            
            // Save the token for future use
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
        }
        
        $this->service = new Google_Service_Calendar($this->client);
    }
    
    public function createEvent($eventData) {
        try {
            $event = new Google_Service_Calendar_Event(array(
                'summary' => $eventData['title'],
                'description' => $eventData['description'],
                'start' => array(
                    'dateTime' => $eventData['start_datetime'],
                    'timeZone' => 'Asia/Manila',
                ),
                'end' => array(
                    'dateTime' => $eventData['end_datetime'],
                    'timeZone' => 'Asia/Manila',
                ),
                'location' => $eventData['location'],
                'attendees' => $eventData['attendees'] ?? [],
                'reminders' => array(
                    'useDefault' => false,
                    'overrides' => array(
                        array('method' => 'email', 'minutes' => 24 * 60),
                        array('method' => 'popup', 'minutes' => 10),
                    ),
                ),
            ));
            
            $calendarId = 'primary'; // Use primary calendar
            $event = $this->service->events->insert($calendarId, $event);
            
            return $event->getId();
        } catch (Exception $e) {
            error_log('Google Calendar Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function updateEvent($eventId, $eventData) {
        try {
            $event = $this->service->events->get('primary', $eventId);
            
            $event->setSummary($eventData['title']);
            $event->setDescription($eventData['description']);
            $event->setLocation($eventData['location']);
            
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($eventData['start_datetime']);
            $start->setTimeZone('Asia/Manila');
            $event->setStart($start);
            
            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($eventData['end_datetime']);
            $end->setTimeZone('Asia/Manila');
            $event->setEnd($end);
            
            $updatedEvent = $this->service->events->update('primary', $eventId, $event);
            return $updatedEvent->getId();
        } catch (Exception $e) {
            error_log('Google Calendar Update Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function deleteEvent($eventId) {
        try {
            $this->service->events->delete('primary', $eventId);
            return true;
        } catch (Exception $e) {
            error_log('Google Calendar Delete Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }
    
    public function handleCallback($code) {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            $this->client->setAccessToken($accessToken);
            
            // Save the token for future use
            $tokenPath = 'config/token.json';
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($accessToken));
            
            return true;
        } catch (Exception $e) {
            error_log('Google Calendar Callback Error: ' . $e->getMessage());
            return false;
        }
    }
}
?>






