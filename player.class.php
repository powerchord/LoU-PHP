<?php

/**
 * Lord of Ultima player class.
 * This class works with an authenticated LoU instance. It provides
 * methods for retrieving and formatting player data.
 * @author Roger Mayfield <pastor_bones@yahoo.com>
 * @copyright Copyright (c) 2012, Roger Mayfield
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @version v0.1.1
 */
Class Player{

    /*
     * Holds the LoU singleton object
     * @private
     * @var mixed
     */
    private $_lou;

    /**
     * Holds retrieved data from LoU
     * @var mixed
     */
    private $_data;

    /**
     * Tells us that we've retrieved our authenticated player's data
     * @var bool
     */
    private $_me = false;

    /**
     * Retrieves and stores player data. If no $id given, retrieves authenticated private data
     * @param int $id
     * @return mixed
     */
    public function __construct( $id = false )
    {
        if(! $this->_lou = LoU::createClient() )
        {
            return false;
        }

        if( !empty( $id ) )
        {
            return $this->getPlayerById( $id );
        }
        
        $this->_me = true;
        return $this->formatPlayer( $this->_lou->get( 'GetPlayerInfo' ) );
    }

    /**
     *  Method for retrieving data by $id
     * @param int $id
     * @return mixed
     */
    public function getPlayerById( $id )
    {
        return $this->formatPublicPlayer( $this->_lou->get( 'GetPublicPlayerInfo', array( 'id' => $id ) ) );
    }

    /**
     * Formats private data returned from LoU. Also uses public data in order
     * to return a full data set
     * @param mixed $player
     * @return mixed
     */
    public function formatPlayer( $player )
    {
        $data = new stdClass();
        $data->id = $player->Id;
        $data->name = $player->Name;

        $public_info = $this->getPlayerById( $player->Id )->getData();

        $data->cities = array();
        foreach( $player->Cities as $c )
        {
            $city = new stdClass();
            $city->id = $c->i;
            $city->name = $c->n;
            $city->ref = $c->r;
            
            foreach( $public_info->cities as $ci )
            {
                if( $city->id == $ci->id )
                {
                    $city->coords = $ci->coords;
                    $city->cont = $ci->cont;
                    $city->castle = $ci->castle;
                    $city->water = $ci->water;
                }
            }
            $data->cities[] = $city;
        }

        $data->alliance = new stdClass();
        $data->alliance->id = $player->AllianceId;
        $data->alliance->name = $player->AllianceName;
        $data->alliance->abbr = $public_info->alliance->abbr;
        $data->alliance->rank = $public_info->alliance->rank;
        
        $data->gold = $player->g;
        $data->mana = $player->m;

        $data->score = $player->p;
        $data->rank = $public_info->rank;

        $data->fame = new stdClass();
        $data->fame->total = $public_info->fame->total;
        $data->fame->rank = $public_info->fame->rank;
        
        $data->plunder = new stdClass();
        $data->plunder->total = $public_info->plunder->total;
        $data->plunder->rank = $public_info->plunder->rank;
        
        $data->units_defeated = new stdClass();
        $data->units_defeated->total = $public_info->units_defeated->total;
        $data->units_defeated->rank = $public_info->units_defeated->rank;

        $data->session = $this->_lou->getSessionInfo();
        
        $this->_data = $data;
        return $this;
    }

    /**
     * Formats public data returned from LoU
     * @param mixed $player
     * @return mixed
     */
    public function formatPublicPlayer( $player )
    {
        $data = new stdClass();
        $data->id = $player->i;
        $data->name = $player->n;

        $data->cities = array();
        foreach( $player->c as $c )
        {
            $city = new stdClass();
            $city->id = $c->i;
            $city->name = $c->n;
            $city->coords = $this->normalize( $c->x ) . ':' . $this->normalize( $c->y );
            $city->cont = $this->getContinentFromCoords( $c->x, $c->y );
            $city->score = $c->p;
            $city->castle = $c->s;
            $city->water = $c->w;
            
            $data->cities[] = $city;
        }
        
        $data->alliance = new stdClass();
        $data->alliance->id = $player->a;
        $data->alliance->name = $player->an;
        $data->alliance->abbr = $player->at;
        $data->alliance->rank = $player->ar;

        $data->score = $player->p;
        $data->rank = $player->r;

        $data->fame = new stdClass();
        $data->fame->total = $player->fup->ft;
        $data->fame->rank = $player->fup->ftr;
        
        $data->plunder = new stdClass();
        $data->plunder->total = $player->fup->p;
        $data->plunder->rank = $player->fup->pr;
        
        $data->units_defeated = new stdClass();
        $data->units_defeated->total = $player->fup->ud;
        $data->units_defeated->rank = $player->fup->udr;

        $this->_data = $data;
        return $this;
    }

    /**
     * Public method for accessing stored data
     * @return mixed
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Takes a pair of x,y coordinates and determines the continent
     * @param int $x
     * @param int $y
     * @return string
     */
    public function getContinentFromCoords( $x, $y )
    {
        $cont = strval( floor( $y / 100 ) ) . strval( floor( $x / 100 ) );
        return 'C' . $this->normalize( $cont, 2 );
    }

    /**
     * Pads a given number by preceding it with $num zeros
     * @param int $val
     * @param int $num
     * @return string
     */
    private function normalize( $val, $num = 3 )
    {
        while( strlen( $val ) < $num )
        {
            $val = '0' . $val;
        }
        return $val;
    }
    
}