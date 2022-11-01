<?php
/**
 * @file
 * Contains \Drupal\influencers\Controller\EstadisticasReferenciadorController.
 */

namespace Drupal\influencers\Controller;

use Drupal\influencers\FuncionesGenerales;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Component\Utility\Tags;
use Drupal\field_collection\Entity\FieldCollection;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\file\Entity\File;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

class EstadisticasReferenciadorController extends ControllerBase {

  public function accessEstadisticas( AccountInterface $account ){
    $result = AccessResult::forbidden();
    if( $account->id() ){
      $result = AccessResult::allowed();
    }
    if( $account->id() == 1 || in_array('administrator', $account->getRoles() ) ){
      $result = AccessResult::allowed();
    }
    return $result;
  }

  public static function estadisticas($tipo = 'usuario'){
    $uid = \Drupal::currentUser()->id();
    if( isset( $_GET['uid'] )){
      $account = \Drupal\user\Entity\User::load( $uid );
      if( $account->id() == 1 || in_array('administrator', $account->getRoles() ) ){
        $uid = $_GET['uid'];
      }
    }
    if(is_array( $tipo )) {
      $_GET=$tipo;
      $uid=$_GET['uid'];
      $periodo = "mes_actual";
      $tipo = 'usuario';
    }elseif( $tipo == 'global' ){
      $uid = 'all';
      $periodo = 'hoy';
      $currency = 'USD';
      $_GET = [
        'uid' => $uid,
        'periodo' => $periodo,
        'currency' => $currency
      ];
    }
    if( isset( $_GET['currency'] )){
      $currency = $_GET['currency'];
    } else {
      $currency = 'MXN';
      $user = \Drupal\user\Entity\User::load( $uid );
      if($user) {
        $pais = $user->get('field_pais')->value;
        if( $pais != '' && $pais != 'MX' ) {
          $currency = 'USD';
        }
      }
    }

    date_default_timezone_set('America/Mexico_City');

    $start    = ( isset($_GET['start_date'] ) ? $_GET['start_date']: date('Y-m-d', strtotime('today') ) );
    $end      = ( isset($_GET['end_date'] ) ? $_GET['end_date']: date('Y-m-d', strtotime('today') ) );
    $periodo  = ( (isset( $_GET['periodo'] ) ) ? $_GET['periodo']: FALSE );
    if( $periodo != FALSE ) {
      switch ( $periodo ) {
        case 'mes_actual':
          $start = date('Y-m-d', strtotime('first day of this month') );
          $end = date('Y-m-d', strtotime('today') );
          break;
        case 'mes_pasado':
          $start = date('Y-m-d', strtotime('first day of last month') );
          $end = date('Y-m-d', strtotime('last day of last month') );
          break;
        case 'hoy':
          $start = date('Y-m-d H:i', strtotime('today') );
          $end = date("Y-m-d H:i:s");
          break;
        case 'ayer':
          $start = date('Y-m-d', strtotime('yesterday') );
          $end = date('Y-m-d', strtotime('yesterday') );
          break;
        case 'semana':
          $start = date('Y-m-d', strtotime('7 days ago') );
          $end = date('Y-m-d', strtotime('today') );
          break;
        default:
          $start = ( isset($_GET['start_date'] ) ? $_GET['start_date']: date('Y-m-d', strtotime('today') ) );
          $end   = ( isset($_GET['end_date'] ) ? $_GET['end_date']: date('Y-m-d', strtotime('today') ) );
          break;
      }
    }

                if(isset($_GET['debug']) && $_GET['debug']=="009") {
                        print_r($start);
                        print "<hr><br/>";
                        print_r($end);
                        print "<hr><br/>";
                        print gmdate('Y-m-d',strtotime($start));
                        print gmdate('Y-m-d',strtotime($end));
        $cache_time=strtotime('+1 minutes');
        if(date("Y-m-d") != gmdate('Y-m-d',strtotime($end))) {
                $cache_time=strtotime('+1 hours');
                print "pozillo";
        }
                        print "<hr><br/>";
                        print $cache_time;
                        die;
                }
    $start_utc = gmdate('Y-m-d',strtotime($start));
    $end_utc = gmdate('Y-m-d',strtotime($end));
    
                //Obtenemos el presupuesto diario del sitio
        $param_country = !empty($_GET['country']) ? $_GET['country'] : "all";
        $param_url = !empty($_GET['url']) ? $_GET['url'] : "all";
        $param_segmento = (!empty($_GET['campo']) && !empty($_GET['dato'])) ? array($_GET['campo'] => $_GET['dato']) : "all";

    $cid = "influencers:gananciasdash:$uid:$start_utc:$end_utc:$currency:$param_country:$param_url:".md5(serialize($param_segmento));
    if ($item = \Drupal::cache()->get($cid)) {
          $info = $item->data;
    }else{
        $cache_time=strtotime('+1 minutes');
        if(date("Y-m-d") != gmdate('Y-m-d',strtotime($end))) {
                $cache_time=strtotime('+1 hours');
        }
        $info = FuncionesGenerales::getGananciasRefers( $uid, $start_utc, $end_utc, $currency, $param_country, $param_url, $param_segmento);
//      \Drupal::cache()->set($cid, $info, $cache_time );
    }

                if(isset($_GET['debug']) && $_GET['debug']=="010") {
                        print "<pre>";
                        print_r($info);
                        print "<hr><br/>";
                        print gmdate('Y-m-d',strtotime($start));
                        print gmdate('Y-m-d',strtotime($end));
                        die;
                }


    $datos = array_values( $info['por_dia'] );
    uasort($datos, function($a,$b) {
      if($a['dia'] == $b['dia']) {
        return 0;
      }
      $dia_a = date('Y-m-d', strtotime($a['dia']));
      $dia_b = date('Y-m-d', strtotime($b['dia']));
      return ($dia_a < $dia_b) ? -1 : 1;
    });

                if(isset($_GET['debug']) && $_GET['debug']=="011") {
                        print "<pre>";
                        print_r($info);
                        print "<hr><br/>";
                        print gmdate('Y-m-d',strtotime($start));
                        print gmdate('Y-m-d',strtotime($end));
                        die;
                }

                if(isset($_GET['debug']) && $_GET['debug']=="012") {
                        print  "<div style='float:right;'>Answered: ". $info['answered'] ."<br/>.40% answ bruto:". $info['answered']*.4 ."<br/>Calculo$$$:". $info['answered']*.4*.74 ."<br/>".number_format($info['answered']/$info['clicks']*100,2,'.','');
                        print "%<br/>%ans/show" .number_format($info['answered']/$info['showed']*100,2,'.','');
                        print "%</div><pre>";
                        print_r($info['gananciaAutor']);
                        print_r($info['gananciaReferencia']);
                        print "</pre>";
                }

    $element = array(
      '#theme' => 'dashboard_estadisticas_usuario',
      '#tipo' => $tipo,
      '#uid' => $uid,
      '#datos' => json_encode( array_values($datos) ),
      '#datos_dia' => $info['por_dia'],
      '#datos_links' => $info['por_link'],
      '#currency' => $currency,
      '#periodo' => $periodo,
      '#start_date' => $start,
      '#end_date' => $end,
      '#ganancia' => $info['ganancia'],
      '#gananciaCountry' => $info['gananciaCountry'],
      '#gananciaAutor' => $info['gananciaAutor'],
      '#gananciaReferencia' => $info['gananciaReferencia'],
      '#bounce_rate' => $info['bounce_rate'],
      '#impresiones' => $info['clicks'],
      '#impresiones_extra' => $info['clicks_extra'],
      '#tiempo_promedio' => $info['tiempo_promedio'],
      '#sesiones_monetizadas' => $info['sesiones_monetizadas'],
      '#impresiones_invalidas' => $info['clicks_invalidos'],
      '#impresiones_completas' => $info['clicks_completos'],
      '#cpm' => ($info['clicks'] > 0) ? (( $info['ganancia'] / $info['clicks']  ) * 1000) : 0
    );
    $element['#attached']['library'][] = 'core/drupal.date';
//    $element['#attached']['library'][] = 'influencers/datepicker';
    $element['#attached']['library'][] = 'influencers/amcharts_sencillo';
    if( $tipo != 'global' ) {
            $element['#attached']['library'][] = 'influencers/ordenar_estadisticas';
    }
    else {
        $element['#attached']['library'][] = 'influencers/ordenar_estadisticas_global';
    }
    return $element;
  }

  public function estadisticasTitle(){
    return t('Stats');
  }

  public function estadisticasTiempoReal(){
    $element = array(
      '#theme' => 'dashboard_estadisticas_tiempo_real'
    );
    $uid = \Drupal::currentUser()->id();
    if( isset( $_GET['uid'] )){
      $account = \Drupal\user\Entity\User::load( $uid );
      if( $account->id() == 1 || in_array('administrator', $account->getRoles() ) ){
        $uid = $_GET['uid'];
      }
    }
    $element['#attached']['drupalSettings']['uid']   = $uid;
    $element['#attached']['drupalSettings']['lastMinutes'] = strtotime('-30 minutes');
    $element['#attached']['library'][] = 'influencers/amcharts_sencillo';
    $element['#attached']['library'][] = 'influencers/amcharts_country';
    $element['#attached']['library'][] = 'influencers/realtime';
    return $element;
  }

  public function estadisticasTiempoRealTitle(){
    return '';
  }

  public function gastoSitio($fake_post = NULL){
    $info = [];

        if($fake_post && empty($_POST)) {
                $_POST = $fake_post;
        }
    if( $_POST ){
      if( isset( $_POST['url'] ) &&  isset( $_POST['start_date'] ) && isset( $_POST['end_date'] ) ){
        $url   = $_POST['url'];
        $start = $_POST['start_date'];
        $end   = $_POST['end_date'];
        if( isset( $_POST['validar_id'] ) ){
          $validar_id = TRUE;
        }else{
          $validar_id = FALSE;
        }
        if( isset( $_POST['autor_id'] ) ){
          $autor_id = $_POST['autor_id'];
        }else{
          $autor_id = FALSE;
        }
        if( isset( $_POST['not_autor_id'] ) ){
          $not_autor_id = $_POST['not_autor_id'];
        }else{
          $not_autor_id = FALSE;
        }
        if( isset( $_POST['array_urls'] ) ){
          $array_urls = $_POST['array_urls'];
        }else{
          $array_urls = FALSE;
        }
//return new JsonResponse($_POST);


        //Cachear esta respuesta para futuras consultas:
        $cid = "infl:api:$start:$end:$validar_id:$autor_id:$not_autor_id:".md5(serialize($array_urls));
        if ($item = \Drupal::cache()->get($cid) && !isset($_POST['debug']) && 4==3) {
                   $info = $item->data;
        }else{
//$pepino["keso"] = "TERRACOTA".rand();
//return new JsonResponse($pepino); 
                $info = FuncionesGenerales::gastoSitio( $url, $start, $end, $validar_id, $autor_id, $not_autor_id, $array_urls );
//$pepino["keso"] = "PANELA".rand();
//return new JsonResponse($pepino);
                if ( !isset($_POST['debug']) ) {
                        \Drupal::cache()->set($cid, $info, strtotime('+5 minutes') );
                }
//$pepino["keso"] = "PANELA".rand();
//return new JsonResponse($pepino);
        }
      }
    }

        if($fake_post && empty($_POST)) {
                return $info;
        }
    return new JsonResponse($info);
  }

  public function presupuestoAgotado(){
    $info = [];
    if( $_POST ){
      if( isset( $_POST['url'] ) ){
        $url   = $_POST['url'];
        //Identificador en el cache
        $cid = 'influencers-agotado:' . $url;

        // Look for the item in cache so we don't have to do the work if we don't need to.
        if ($item = \Drupal::cache()->get($cid)) {
          $info = $item->data;
        } else {
          // Build up the markdown array we're going to use later.
          $info = FuncionesGenerales::getAgotado( $url );

          // Set the cache so we don't need to do this work again until $node changes.
          \Drupal::cache()->set($cid, $info, time() + 3000);
        }
      }
    }
    return new JsonResponse($info);
  }
/* 
  public function presupuestoAgotado2(){
    $info = [];
    if( $_POST ){
      if( isset( $_POST['url'] ) ){
        $url   = $_POST['url'];

        //Identificador en el cache
        $cid = 'influencers-agotado:' . $url;

        // Look for the item in cache so we don't have to do the work if we don't need to.
        if ($item = \Drupal::cache()->get($cid)) {
          $info = $item->data;
        }
        else {
          // Build up the markdown array we're going to use later.
          $info = FuncionesGenerales::getAgotado( $url );

          $markdown = [
            'gasto' => $info,
            'agotado' => $info->agotado
          ];
          // Set the cache so we don't need to do this work again until $node changes.
          \Drupal::cache()->set($cid, $markdown, time() + 3000);
        }

        // $info = FuncionesGenerales::getAgotado( $url );
      }
    }
 
    return new JsonResponse($info);
  }*/
}

?>
