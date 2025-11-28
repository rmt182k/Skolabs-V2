<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />
    <meta content="Coderthemes" name="author" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Plugin css -->
    <link href="{{ asset('assets/vendor/daterangepicker/daterangepicker.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/vendor/jsvectormap/jsvectormap.min.css') }}" rel="stylesheet" type="text/css">

    <!-- Theme Config Js -->
    <script src="{{ asset('assets/js/hyper-config.js') }}"></script>

    <!-- Vendor css -->
    <link href="{{ asset('assets/css/vendor.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />

    <!-- Icons css -->
    <link href="{{ asset('assets/css/unicons/css/unicons.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/remixicon/remixicon.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/mdi/css/materialdesignicons.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Datatables css -->
    <link href="{{ asset('assets/vendor/datatables/responsive.bootstrap5.min.css') }}" rel="stylesheet"
        type="text/css">
    <!-- For checkbox Select-->
    <link href="{{ asset('assets/vendor/datatables/select.bootstrap5.min.css') }}" rel="stylesheet" type="text/css">
    <!-- For Buttons -->
    <link href="{{ asset('assets/vendor/datatables/buttons.bootstrap5.min.css') }}" rel="stylesheet" type="text/css">
    <!-- Fixe header-->
    <link href="{{ asset('assets/vendor/datatables/fixedHeader.bootstrap5.min.css') }}" rel="stylesheet"
        type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-K5mQJ6E7E9KcQ1w2Z+Z7tprlZxqNftwbMZQjzW1zBq5uWZbnIBzj1iX3A9JjX9L4gXKwI3+2nDZnZP5h0ZpJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @stack('styles')
</head>

<body>
    <!-- Begin page -->
    <div class="wrapper">


        <!-- ========== Topbar Start ========== -->
        @include('layouts.components.topbar')
        <!-- ========== Topbar End ========== -->

        <!-- ========== Left Sidebar Start ========== -->
        @include('layouts.components.sidebar')
        <!-- ========== Left Sidebar End ========== -->

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div class="content">

                <!-- Start Content-->
                @yield('content')
                <!-- container -->

            </div>
            <!-- content -->

            <!-- Footer Start -->
            @include('layouts.components.footer')
            <!-- end Footer -->

        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

    <!-- Theme Settings -->
    @include('layouts.components.theme-settings')

    <!-- Vendor js -->
    <script src="{{ asset('assets/js/vendor.min.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <!-- Daterangepicker js -->
    <script src="{{ asset('assets/vendor/moment/moment.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/daterangepicker/daterangepicker.js') }}"></script>

    <!-- Charts js -->
    <script src="{{ asset('assets/vendor/chart.js/chart.umd.js') }}"></script>
    <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Vector Map js -->
    <script src="{{ asset('assets/vendor/jsvectormap/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/jsvectormap/world-merc.js') }}"></script>
    <script src="{{ asset('assets/vendor/jsvectormap/world.js') }}"></script>

    <!-- Analytics Dashboard App js -->
    <script src="{{ asset('assets/js/pages/demo.dashboard-analytics.js') }}"></script>



    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Code Highlight js -->
    <script src="{{ asset('assets/vendor/prismjs/prism.js') }}"></script>
    <script src="{{ asset('assets/vendor/prismjs/prism-normalize-whitespace.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/clipboard/clipboard.min.js') }}"></script>
    <script src="{{ asset('assets/js/hyper-syntax.js') }}"></script>

    <!-- Datatables js -->
    <script src="{{ asset('assets/vendor/datatables/dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/responsive.bootstrap5.min.js') }}"></script>
    <!-- Buttons -->
    <script src="{{ asset('assets/vendor/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/buttons.print.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/vfs_fonts.js') }}"></script>
    <!-- Select-->
    <script src="{{ asset('assets/vendor/datatables/dataTables.select.min.js') }}"></script>
    <!-- Fixed Header-->
    <script src="{{ asset('assets/vendor/datatables/dataTables.fixedHeader.min.js') }}"></script>
    <script src="{{ asset('assets/js/app/utils/sidebar.js') }}"></script>
    @stack('scripts')

</body>

</html>
