<?
/* Random.class.php - Static class for common random faculties
 * Copyright (C) 2007 Erik Osterman <e@osterman.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/* File Authors:
 *   Erik Osterman <e@osterman.com>
 */


class Random
{
	// /dev/random blocks until enough entropy bits are collected in the entropy pool
	// /dev/urandom doesn't block, but will be less random
	const DEVICE = '/dev/urandom';
	
	public static function bytes( $size )
	{
		static $fh;
		if( !is_resource($fh) )
		{
			$fh = fopen( Random::DEVICE, 'r' );
			if( $fh === FALSE )
				throw new Exception( __CLASS__ . "::bytes failed to open device " . Random::DEVICE );
		}
		
		$rand = fread( $fh, $size );
		if( strlen($rand) == $size )
			return $rand;
		else
			throw new Exception( __CLASS__ . "::bytes failed to read $size bytes. Got " . strlen($rand) );
	}

	public static function integer()
	{
		// By default, rand returns an integer	
		$args = func_get_args();
		if(count($args) == 2 )
			return rand( $args[0], $args[1] );
		elseif( count($args) == 1)
			return rand( 0, $args[0] );
		else
			return rand();
	}
	
	public static function real()
	{
		$args = func_get_args();
		if(count($args) == 2 )
			return (double) ( rand( $args[0], $args[1] - 1 ) . "." . rand() );
		elseif( count($args) == 1)
			return (double) ( rand( 0, $args[0] - 1 ) . "." . rand() );
		else
			return (double)( rand() . '.' . rand() );
	}

  // Returns a random, available port. Note, there is no locking, so it's not guaranteed to be available upon binding.
  public static function port( $protocol, $ip, $start_port, $end_port )
  {
    /* 
      Active Internet connections (only servers)
      Proto Recv-Q Send-Q Local Address               Foreign Address             State
      tcp        0      0 216.55.132.136:8000         0.0.0.0:*                   LISTEN
      tcp        0      0 0.0.0.0:11211               0.0.0.0:*                   LISTEN
      tcp        0      0 :::80                       :::*                        LISTEN
     */

    $netstat = new Pipe("/bin/netstat -n -l --numeric-ports");
    $netstat->execute();
    $reserved = Array();
    foreach( $netstat as $buffer )
    {
      if( preg_match("/^$protocol.*LISTEN\s*$/i", $buffer ) )
      {
        //print $buffer;
        list( $proto, $recv_queue, $send_queue, $local_address ) = preg_split('/\s+/', $buffer );
        list( $local_ip, $local_port ) = preg_split('/:+/', $local_address);
        //print "[$local_address] [$local_ip] [$local_port]\n";
  
        if( ( $local_ip == '0.0.0.0' || $ip == '0.0.0.0' )
              || $local_ip == '' || $local_ip == $ip )
          array_push( $reserved, $local_port );
      }
    }
    
    //print_r($reserved);
    for( $i = 0; $i < $end_port - $start_port; $i++ )
    {
      $free_port = Random::integer( $start_port, $end_port );
      if( ! in_array($free_port, $reserved ) )
        return $free_port;
      //else print "Collision $free_port\n";
    }
    throw new Exception( __CLASS__ . "::port exhaused all free ports in [$start_port, $end_port]");
  }

	
}


/*
// Example Usage
print Random::port( 'tcp', '0.0.0.0', 8000, 8010 ) . "\n"
print UUID::text( Random::bytes(16) ) . "\n";
print UUID::text( Random::bytes(16) ) . "\n";
print Random::real(0,1) . "\n";
print Random::real() . "\n";
print Random::integer() . "\n";
*/

?>
