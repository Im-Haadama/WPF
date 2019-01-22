<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 12/05/17
 * Time: 15:25
 */

require_once 'tools/PHPExcel/Classes/PHPExcel/IOFactory.php';


// Check prerequisites
//print sfConfig::get("sf_upload_dir").DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR; exit;
//if (!file_exists($file)) {
//    exit($file."Please run 06largescale.php first.\n");
//}

$objReader = PHPExcel_IOFactory::createReader( 'Excel2007' );

$objPHPExcel = $objReader->load( $file );

$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'CSV' );

$objWriter->save( str_replace( '.xlsx', '.csv', $file ) );
return "success";