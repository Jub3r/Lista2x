<?php
/* dudas:
 * obtener valoracion semanal de canciones/playlist
 * limpiar datos antes de llamar a la db o hacerlo en el controlador?
 */

    function conexion()
    {
        include("globales.php");
        $con = mysql_connect($servidor, $usuario, $contraseña);
        $bd = mysql_select_db($basedatos, $con);
        mysql_set_charset('utf8');
        return $con;
    }
    
    function mLogin($uid, $pw)
    {
        $usuario = addslashes($uid);
        $contraseña = addslashes($pw);
		$con = conexion();
        $resultado = mysql_query("SELECT count(id) FROM usuarios WHERE usuario='" . $usuario . "' and contraseña='" . md5($contraseña) . "'",$con) or die("Error en: " . mysql_error());
		$comprobacion = mysql_fetch_array($resultado);
        if ($comprobacion[0] == 1)
        {
			return $usuario;
        }
		else
		{
			return false;
		}
    }
	
	function mToplistas()
	{
		$con = conexion();
		$resultado = mysql_query("select *, (ValoracionSemanal * 8) as ValoracionSemanal from playlist order by valoracion desc limit 20;",$con);
		return $resultado;   
	}
	
	function mTopcanciones()
	{
		$con = conexion();
		$resultado=mysql_query("select *, (ValoracionSemanal * 8) as ValoracionSemanal from canciones order by valoracion desc limit 20;",$con);
		return $resultado;
	}
	
	function mMislistas($usuario)
	{
		$con = conexion();
		$resultado = mysql_query("select *, (Valoracion * 8) as Valoracion from playlist WHERE usuario ='" . $usuario . "' order by valoracion desc limit 20;",$con);
		return $resultado;   
	}
	
	function mMisfavoritos($usuario)
	{
		$con = conexion();
		$resultado = mysql_query("select *, (Valoracion * 8) as Valoracion from puntuacioncanciones WHERE usuario ='" . $usuario . "' order by puntuacion desc limit 20;",$con);
		return $resultado;   
	}

	function mreportes()
	{
		$con = conexion();
		$resultado=mysql_query("select * from comentarios order by reportes limit 20;",$con);
		return $resultado;   
	}
	function altausuario($nombre,$apodo,$correo,$ucontraseña)
	{
		$con=conexion();
		mysql_real_escape_string($nombre);
		mysql_real_escape_string($apodo);
		mysql_real_escape_string($correo);
		mysql_real_escape_string($ucontraseña);
		$cifrado=sha1($ucontraseña);
		$resultado=mysql_query("insert into usuarios(nombre,usuario,correo,contraseña) values ('$nombre','$apodo','$correo','$cifrado');",$con); 
		return $resultado;   
	}
                /////////////////////////////////////////////////////////////////
        function mIsAdmin($usuario){
            $con=conexion();
            $resultado=mysql_query("select usuario from Administradores where usuario=$usuario",$con);
            $isusuario = mysql_fetch_array($resultado);
            if ($isusuario==false){
                return false;
            }else{
                return true;
            }
        }
        function subirArchivo($archivo,$path,$nombre,$extension,$tamMaximo){
            if ($archivo['size']>$tamMaximo/* || !substr_compare($archivo['name'],$extension, strlen($archivo['name'])-3,3,true)*/){
                return false;
            }
            $res= move_uploaded_file ($archivo['tmp_name'] ,$path.'/'.$nombre);
            return $res;
        }
        function adminlogin($admin,$contraseña){
            if (!isset($admin,$contraseña)){
                    return false;
            }
            $con=conexion();
            mysql_real_escape_string($admin);
            mysql_real_escape_string($contraseña);
            $cifrado=sha1($contraseña);
            $consulta="select usuario from administradores where contraseña='$cifrado' and usuario='$admin'";
            $resultado=mysql_query($consulta,$con);
            $fusuario = mysql_fetch_assoc($resultado);
            if ($fusuario==false) {        
                return false;
            }else{
                return $fusuario['usuario'];       
            }
        }
        function añadirCancion($titulo,$autor,$album,$genero,$año,$imagen,$cancion){
            if (!isset($titulo,$autor,$album,$genero,$año,$imagen,$cancion)){ //estan todos los datos
                return false;
            }
            $con=conexion();
            mysql_real_escape_string($titulo);
            mysql_real_escape_string($autor);
            mysql_real_escape_string($album);
            mysql_real_escape_string($genero);
            mysql_real_escape_string($año);
            mysql_query("begin",$con);
            $result=  mysql_query("insert into canciones(Titulo,Autor,Album,Genero,Año) values ('$titulo','$autor','$album','$genero','$año')",$con);
            if ($result===false){ //ya existe en la DB, no lo subimos de nuevo
                mysql_query("rollback",$con);
                return false;
            }
            $nomarchivo= mysql_insert_id();
            $ar1=subirArchivo($imagen, "./caratulas",$album.'.jpg', 'jpg',3000000);
            $ar2=subirArchivo($cancion, "./canciones",$nomarchivo.'.mp3', 'mp3',10000000);
            if ($ar1 ==true && $ar2==true){ //compruebo si se han subido los archivos
                mysql_query("commit",$con);//correcto, confirmo cambios
                return true;
            }else{ //fallo en la subida de archivos, desago el insert ¿borrar lo subido?
                mysql_query("rollback",$con);//tiramos pa atras
                return false;
            }
                
        }
     function mbuscartitulo($palabra){
        $con = conexion();
	$resultado = mysql_query("select * from canciones WHERE titulo like '%$palabra%'",$con);
	$i=0;
        $aux=null;
        if ($resultado!==false) {
            while ($cancion = mysql_fetch_assoc($resultado)) {
                $aux[$i]=$cancion;
                $i++;
            }
        }
        return $aux;   
     }
     function mbuscarautor($palabra){
        $con = conexion();
	$resultado = mysql_query("select autor,count(distinct album) albumnes,count(id) canciones from canciones where autor like '%$palabra%' group by autor;",$con);
	$i=0;
        $aux=null;
        if ($resultado!==false) {
            while ($cancion = mysql_fetch_assoc($resultado)) {
                $aux[$i]=$cancion;
                $i++;
            }
        }
        return $aux;   
     }
    function mbuscaralbum($palabra){
        $con = conexion();
	$resultado = mysql_query("select autor, album,count(id) canciones from canciones where album like '%$palabra%' group by album;",$con);
	$i=0;
        $aux=null;
        if ($resultado!==false) {
            while ($cancion = mysql_fetch_assoc($resultado)) {
                $aux[$i]=$cancion;
                $i++;
            }
        }
        return $aux;   
     }
     function mborrarCancion($id){
        $con = conexion();
        if (!isset($id)){
            return false;
        }
        mysql_real_escape_string($id);
	$resultado = mysql_query("SELECT album,count(id) 'n' from canciones where album=(select album from canciones where id='$id') group by album",$con);
        if ($resultado===false || mysql_num_rows($resultado)!=1) {
            return false;//no existe el id
        }
        $datos = mysql_fetch_assoc($resultado);
        mysql_free_result($resultado);
        $otro = mysql_query("delete from canciones where id='$id'",$con);
        if ($otro===false){
            return false;//no se ha podido eliminar la cancion
        }
        unlink("canciones/$id.mp3");//borramos la cancion
        if ($datos['n']==1){//es la ultima cancion del albun, asi que tambien borramos la caratula del album
              unlink("caratulas/".$datos['album'].".jpg");
        }
        return true;
     }
     function cancionesautor($autor){
        $con = conexion();
	$resultado = mysql_query("select * from canciones where autor like '$autor'",$con);
	$i=0;
        $aux=null;
        if ($resultado!==false) {
            while ($cancion = mysql_fetch_assoc($resultado)) {
                $aux[$i]=$cancion;
                $i++;
            }
        }
        return $aux; 
     }
     function cancionesalbum($album){
        $con = conexion();
	$resultado = mysql_query("select * from canciones where album like '$album'",$con);
	$i=0;
        $aux=null;
        if ($resultado!==false) {
            while ($cancion = mysql_fetch_assoc($resultado)) {
                $aux[$i]=$cancion;
                $i++;
            }
        }
        return $aux; 
     }
?>