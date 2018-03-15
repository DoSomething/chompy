@extends('layouts.master')

@section('main_content')

    <div>
        <div>
            <div>
                <form action={{url('/import')}} method="post" enctype="multipart/form-data">
                    {{ csrf_field()}}
                    <div>
                        <label for="upload-file">Upload</label>
                        <input type="file" name="upload-file">
                    </div>

                    <label>Type of Import</label>

                    <div>
                        <label>
                            <input checked type="checkbox" id="importType" value="turbovote" name="importType">
                            <span>Turbovote Import</span>
                        </label>
                    </div>
                    <div>
                        <input type="submit" value="Submit CSV">
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop
