<?php
$e = array(
  E_WARNING => 'E_WARNING',
  E_NOTICE => 'E_NOTICE',
  E_USER_ERROR => 'E_USER_ERROR',
  E_USER_WARNING => 'E_USER_WARNING',
  E_USER_NOTICE => 'E_USER_NOTICE',
  E_STRICT => 'E_STRICT',
  E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
  E_DEPRECATED => 'E_DEPRECATED',
  E_USER_DEPRECATED => 'E_USER_DEPRECATED',
  E_ALL => 'E_ALL',
  );
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Error</title>
  <style>
  table {
    width: 100%;
    margin-bottom: 18px;
    padding: 0;
    font-size: 12px;
    border-collapse: separate;
    border-spacing: 0;
    border: 1px solid #DDD;
    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
    border-radius: 4px;
  }
  td {
    vertical-align: top;
    border-top: 1px solid #DDD;
    line-height: 18px;
    text-align: left;
    padding:5px 5px 4px;
    border-left: 1px solid #DDD;
  }
  tr{
    border-bottom:1px solid #f5f5f5;
    -webkit-transition:background .2s ease;
    -moz-transition:background .2s ease;
    -ms-transition:background .2s ease;
    -o-transition:background .2s ease;
    transition:background .2s ease
  }
  tr:hover{
    background:#F3F3F3;
    -webkit-transition:none;
    -moz-transition:none;
    -ms-transition:none;
    -o-transition:none;
    transition:none
  }
  thead {
    background-color: #F3F3F3;
    font-size: 14px;
  }
  </style>
</head>
<body>
  <table>
    <thead>
      <?php if(isset($exception)):?>
        <?php $line = $exception->getLine();?>
        <?php $file = $exception->getFile();?>
        <?php $backtrace = $exception->getTrace();?>
        <tr>
          <td colspan="3">
            <b><?php echo 'Exception：',$exception->getMessage(),' in ',$file,' on line ',$line ?></b>
          </td>
        </tr>
      <?php endif;?>
      <?php if(isset($errno)):?>
        <?php $line = $errline;?>
        <?php $file = $errfile;?>
        <?php $backtrace = debug_backtrace();?>
        <tr>
          <td colspan="3">
            <b><?php echo $e[$errno],'：',strip_tags($errstr),' in ',$file,' on line ',$line ?></b>
          </td>
        </tr>
      <?php endif;?>
      <tr>
        <td colspan="3">
          <b>Call Stack</b>
        </td>
      </tr>
      <tr>
        <td>
          <b>#</b>
        </td>
        <td>
          <b>Function</b>
        </td>
        <td>
          <b>Location</b>
        </td>
      </tr>
    </thead>
    <tbody>
      <?php for ($i=count($backtrace)-1,$j=1;$i>=0;--$i,++$j):?>
        <?php $line=isset($backtrace[$i]['line'])?$backtrace[$i]['line']:$line;$file=isset($backtrace[$i]['file'])?$backtrace[$i]['file']:$file;?>
        <tr>
          <td>
            <?php echo $j;?>
          </td>
          <td>
            <?php if(isset($backtrace[$i]['class'])):?><?php echo isset($backtrace[$i]['class'])?$backtrace[$i]['class'].$backtrace[$i]['type']:''?><?php endif;?><?php echo $backtrace[$i]['function']?>
            <?php echo isset($backtrace[$i]['args'])?preg_replace(array('/^Array/','/\[0\] => /', '/\[\d+\] => /'), array('','',','), print_r($backtrace[$i]['args'],true)):'( )'?>
          </td>
          <td>
            <?php echo $file,' on line ',$line;?>
          </td>
        </tr>
      <?php endfor;?>
    </tbody>
  </table>
</body>
</html>