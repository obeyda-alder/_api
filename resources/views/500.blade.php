<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Error</title>
    <link href="{{ Module::asset('cms:vendors/css/vendor.bundle.base.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ Module::asset('cms:css/style.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ Module::asset('cms:css/style.css.map') }}" rel="stylesheet" type="text/css" />
    <link href="{{ Module::asset('cms:vendors/chartist/chartist.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ Module::asset('cms:vendors/flag-icon-css/css/flag-icon.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ Module::asset('cms:vendors/daterangepicker/daterangepicker.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ Module::asset('cms:vendors/select2/select2.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ Module::asset('cms:vendors/select2-bootstrap-theme/select2-bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ Module::asset('cms:vendors/simple-line-icons/css/simple-line-icons.css') }}" rel="stylesheet" type="text/css">
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center text-center error-page bg-info">
          <div class="row flex-grow">
            <div class="col-lg-7 mx-auto text-white">
              <div class="row align-items-center d-flex flex-row">
                <div class="col-lg-6 text-lg-right pr-lg-4">
                  <h1 class="display-1 mb-0">500</h1>
                </div>
                <div class="col-lg-6 error-page-divider text-lg-left pl-lg-4">
                  <h2>SORRY!</h2>
                  <h3 class="font-weight-light">Internal server error!</h3>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
