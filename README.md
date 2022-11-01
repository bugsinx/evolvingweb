# evolvingweb
This file it's a Controller to manage statistics information of the web platform influencers.devconsultores.com, here it manage the overall permissions to view statistics for each user and user role, also manage and API endpoint to send JSON statistics information to another project tuul.tv and build the main dashboard view of the statistics.

This are the main methods of the controller:
  public function accessEstadisticas( AccountInterface $account )    //Check access permissions for user roles
  public static function estadisticas($tipo = 'usuario')             //Build the dashboard information for the dashboard view template
  public function estadisticasTitle()                                //Function requeried by Drupal standards to manage the view title
  public function estadisticasTiempoReal()                           //Build the dashboard information for the realm time statistics view template
  public function estadisticasTiempoRealTitle()                      //Function requeried by Drupal standards to manage the view title
  public function gastoSitio($fake_post = NULL)                      //API Endpoint to return stastics information in JSON format   
  public function presupuestoAgotado()                               //Budget information for inside campaigns to complement statistics 
  

The main functions mostly only collect the information and prepare it to be more managable to build the dashboards views, the heavy logic it's inside the "FuncionesGenerales" class

I have some if(isset($_GET['debug']) && $_GET['debug']=="009") snippets around the code for debugging purpose of some test cases 

All this comments and debug things i use it on my local version but commiting for QA/production i remove all this comments and debugs

  I decided to build this this way to have a central point to control information for templating all statistics managed by the site and also to comply with Drupal MVC and Symfony standards, also i use the "debug ifs" as an easy way to test some edge cases without using more advanced tools

