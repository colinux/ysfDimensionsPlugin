<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <?php include_http_metas(); ?>
  <?php include_metas(); ?>
  <?php include_stylesheets(); ?>
  <?php include_javascripts(); ?>
  <?php include_title(); ?>
</head>
<body>
  <h1>corp layout</h1>
  <?php echo $this->getAttributeHolder()->isEscaped() ? $sf_data->getRaw('sf_content') : $sf_content; ?>
</body>
</html>
