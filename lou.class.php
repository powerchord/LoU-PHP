<?php

/**
 * Lord of Ultima connection class.
 * This class connects, authenticates, and provides basic methods
 * for retrieving data from LoU via EndPoint designations.
 * @author Roger Mayfield <pastor_bones@yahoo.com>
 * @copyright Copyright (c) 2012, Roger Mayfield
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @version v0.1.0
 */
Class LoU
{

    /**
     * Path where cookies should be saved.
     * @var string
     */
    private $_cookie_path;

    /**
     * Name of our cookie file.
     * @var string
     */
    private $_cookie_file;

    /**
     *  World ID of the LoU server to connect to
     * @var int
     */
     private $_world_id;

    /**
     * The list of available LoU Servers
     * @var mixed
     */
     public $server_list = array();

    /**
     * Contains all our session information
     * @var mixed
     */
     private $_session;

     /**
      * Store our object instance to achieve the singleton pattern
      * @private
      * @var mixed
      */
      private static $_instance;

    /**
     * Placeholder for error message.
     * Contains short description of an error after the error happens.
     * @var string
     */
    public $error = '';

    /**
     * Object Constructor
     * @private
     * @param string $cookie_path
     * @return mixed
     */
    private function __construct( $cookie_path )
    {
        // Set cookie path
        $this->_cookie_path = ( substr_compare( $cookie_path, '/', -1, strlen( $cookie_path ) ) === 0 ) ? $cookie_path . '/' : $cookie_path;
        return $this;
    }

    public function createClient( $cookie_path=false )
    {
        if( !self::$_instance )
        {
            if(  !empty( $cookie_path ) )
            {
                self::$_instance = new LoU( $cookie_path );
            }
            else
            {
                return false;
            }
        }

        return self::$_instance;
    }

    /**
     * Logins to LoU.
     * Authenticates a LoU account by using an email and password. If the world id
     * is set previously, it will open a session. Returns $this for method-chains.
     * 
     * @param string $email LoU account email
     * @param string $pass LoU account password
     * @return mixed
     */
    public function login( $email, $pass )
    {
        if( empty( $this->cookieFile ) )
        {
            $this->_cookie_file = $this->generateCookieFile( $email );
        }

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, 'http://www.lordofultima.com/en/' );
        curl_setopt( $ch, CURLOPT_REFERER, 'http://www.lordofultima.com/en/' );
        curl_setopt( $ch, CURLOPT_COOKIEFILE, $this->_cookie_file );
        curl_setopt( $ch, CURLOPT_COOKIEJAR, $this->_cookie_file );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
        curl_exec( $ch );

        $fields = array( 'mail' => $email, 'password' => $pass );
        $field_str = http_build_query( $fields );
        curl_setopt( $ch, CURLOPT_URL, 'https://www.lordofultima.com/en/user/login/' );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_str );
        $result = curl_exec( $ch );

        if( strstr( $result, "fill in a correct email" ) )
        {
            $this->error = 'Invalid username and/or password.';
            return false;
        }

        require_once( './simple_html_dom.php' );
        $html = new simple_html_dom();
        $html->load( $result );

        $e = $html->find( '.server-list', 0 )->find( '.menu_bubble' );
        $sessId = $e[ 0 ]->find( '#sessionId', 0 )->value;

        foreach( $e as $v )
        {
            $world = new stdClass();
            $world->name = $v->find( '.inner', 0 )->value;
            preg_match( "/World (\d+)/i", $world->name, $match );
            $world->id = $match[ 1 ];
            preg_match( "/prodgame(\d+)\.lordofultima\.com\/(\d+)/i", $v->find( 'form', 0 )->action, $match );
            $world->server = "http://prodgame" . $match[ 1 ] . ".lordofultima.com/" . $match[ 2 ];
            
            array_push( $this->server_list, $world );
        }

        $this->_session = new stdClass();
        $this->_session->id = $sessId;

        // Select world if given
        if( !empty( $this->_world_id ) )
        {
            return $this->selectWorld( $this->_world_id );
        }

        return $this;
    }

    /**
     * Sets the World ID to use when querying the LoU server.
     * 
     * @param int $world_id
     * @return mixed
     */
    public function setWorld( $world_id )
    {
        if( !is_numeric( $world_id ) )
        {
            $this->error = 'Invalid World ID';
            return false;
        }
        
        $this->_world_id = $world_id;
        return $this;
    }

    /**
     * Selects the world using the given world id.
     * 
     * @param int $world_id
     * @return mixed
     */
    public function selectWorld( $world_id )
    {
        if( !$this->setWorld( $world_id ) instanceof $this )
        {
            return false;
        }

        foreach($this->server_list as $world){
            if( $world->id == $world_id )
            {
                if( !$sess_key = $this->getSessKey( $world->server, $this->_session->id ) )
                {
                    $this->error = 'Invalid server and/or session id';
                    return false;
                }
                $this->_session->key = $sess_key;
                $this->_session->world = $world->name;
                $this->_session->server = $world->server;
            }
        }

        return $this;
    }

    /**
     * Retrieves LoU session key.
     * @param string $sess_id
     * @return string
     */
    public function getSessKey( $url, $sess_id )
    {
        $result = $this->getData( $url, 'OpenSession', array('session' => $sess_id, 'rest' => 'false' ) );
        
        if( isset( $result->i ) )
        {
            return $result->i;
        }
        return false;
    }

    /**
     * Retrieves data from a given endpoint
     * @param string $url
     * @param string $endpoint
     * @param mixed $data
     * @return mixed
     */
    public function getData( $url, $endpoint, $data )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, "$url/Presentation/Service.svc/ajaxEndpoint/$endpoint" );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Content-Type: application/json; charset=utf-8", "Cache-Control: no-cache", "Pragma: no-cache", "X-Qooxdoo-Response-Type: application/json" ) );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, str_replace( '\\\\', '\\', json_encode( $data ) ) );
        curl_setopt( $ch, CURLOPT_COOKIEFILE, $this->_cookie_file );
        curl_setopt( $ch, CURLOPT_COOKIEJAR, $this->_cookie_file );
        $result = curl_exec( $ch );
        curl_close( $ch );

        if( empty( $result ) )
        {
            $this->error = 'LoU returned an empty result';
            return false;
        }
        
        return json_decode( $result );
    }

    /**
     * Retrieves data from given endpoint. Does not require the session id to be placed in the data.
     * @param string $endpoint
     * @param mixed $data
     */
    public function get( $endpoint, $data=array() )
    {
        $data = array_merge( array( 'session' => $this->_session->key ), $data );
        return $this->getData( $this->_session->server, $endpoint, $data);
    }

    /**
     * Formats given requests and retrieves data from the endpoint Poll
     * @param mixed $requests
     * @return mixed
     */
    public function poll( $requests )
    {
        $data = array( 'requestid' => mt_rand( 1000, 9999 ), 'requests' => '' );
        foreach( $requests as $key => $val )
        {
            $data['requests'] .= strtoupper( $key ) . ':' . $val . '\f';
        }

        return $this->get( 'Poll', $data );
    }

    /**
     * Generates a name for our cookiefile.
     * @private
     * @param string
     * @return string
     */
    private function generateCookieFile( $id )
    {
        return $this->_cookie_path . md5($id) . 'txt';
    }
}

