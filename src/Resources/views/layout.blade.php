<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Query Builder - Manage Database</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css" />
    <link href="https://unpkg.com/tabulator-tables/dist/css/tabulator.min.css" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="site-url" content="{{ url('/') }}">

    <script type="text/javascript">
        var site_url = '{{ url('/') }}';
    </script>

    <style>

        body {
            padding-top: 56px;
        }

        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        .sidebar .nav-link.active {
            color: #007bff;
        }
        .main-content {
            margin-left: 200px;
            padding: 20px;
        }

        /* -------------- loader start --------------------- */
        .loader {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /* -------------- loader end --------------------- */

        @media (max-width: 767.98px) {
            .sidebar {
                top: 5rem;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>

    @yield('css')

</head>
<body>
    
    @include( 'wc_querybuilder::header' )

    <div class="container-fluid">
        <div class="row">

            @include( 'wc_querybuilder::sidebar' )

            <main class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4">

                @yield('content')

            </main>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script type="text/javascript" src="https://unpkg.com/tabulator-tables/dist/js/tabulator.min.js"></script>

    <script type="text/javascript">

        toastr.options.closeButton = true;
        
        document.addEventListener("DOMContentLoaded", function(){
            document.querySelectorAll('.sidebar .nav-link').forEach(function(element){

                element.addEventListener('click', function (e) {

                    let nextEl = element.nextElementSibling;
                    let parentEl  = element.parentElement;    

                    if(nextEl) {
                        e.preventDefault(); 
                        let mycollapse = new bootstrap.Collapse(nextEl);

                        if(nextEl.classList.contains('show')){
                            mycollapse.hide();
                        } else {
                            mycollapse.show();

                            var opened_submenu = parentEl.parentElement.querySelector('.submenu.show');

                            if(opened_submenu){
                                new bootstrap.Collapse(opened_submenu);
                            }
                        }
                    }
                }); 
            }) 
        }); 

        $(document).ready(function() {

            

        });

    </script>
    
    @yield('scripts')

</body>
</html>