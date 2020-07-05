
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <title>Import xml</title>

    <!-- Bootstrap core CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">

</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <a class="navbar-brand" href="#">Navbar</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarsExampleDefault">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Link</a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Dropdown</a>
                <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <a class="dropdown-item" href="#">Action</a>
                    <a class="dropdown-item" href="#">Another action</a>
                    <a class="dropdown-item" href="#">Something else here</a>
                </div>
            </li>
        </ul>
        <form class="form-inline my-2 my-lg-0">
            <input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
            <button class="btn btn-secondary my-2 my-sm-0" type="submit">Search</button>
        </form>
    </div>
</nav>

<main role="main" class="container">

    <div class="row justify-content-center mt-5">
        <div class="col-6">
            <h2 class="text-center">Please, upload your xml file</h2>
            <div>
                Allowed max file size: {{ $maxSizeMb }}.
            </div>
            <form action="{{ route("import-xml") }}" class="mt-4" enctype="multipart/form-data" method="POST">
                <div class="custom-file">
                    <input name="xml_content" type="file" class="custom-file-input" id="customFile">
                    <label class="custom-file-label" for="customFile">Choose file</label>
                    <button type="submit" class="btn btn-block btn-primary mt-2">Import</button>
                </div>
                @csrf
            </form>
            <div class="mt-3">
                @if ($errors->any())
                    <ul id="errors">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="mt-3">
                @if ($report = Session::get('report'))
                    Execution time: {{ $report['execution_time'] }} sec. <br>
                    Imported products total count: {{ $report['count'] }} <br>
                    Not imported products total count: {{ $report['failed_count'] }} <br>
                    @if ($report['duplicates'] or $report['broken_data'])
                        Details about failed imported products:<br>
                    @endif
                    @if ($report['broken_data'])
                        Broken source data (code field value): <br>
                        <ul id="errors">
                            @foreach($report['broken_data'] as $product_code)
                                <li>{{ $product_code }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($report['duplicates'])
                        Duplicated products (code field value): <br>
                        <ul id="errors">
                            @foreach($report['duplicates'] as $product_code)
                                <li>{{ $product_code }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>
        </div>
    </div>

</main><!-- /.container -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
