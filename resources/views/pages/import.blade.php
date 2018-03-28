@extends('layouts.master')

@section('main_content')

    <div>
        <div>
            <div>
                <form action={{url('/import')}} method="post" enctype="multipart/form-data">
                    {{ csrf_field()}}
                    <div class="form-group">
                        <div class="input-group input-file" name="file-upload">
                            <span class="input-group-btn">
                                <button class="btn btn-default btn-choose" type="button">Choose</button>
                            </span>
                            <input type="text" class="form-control" placeholder='Choose a file...' />
                            <span class="input-group-btn">
                                <button class="btn btn-danger btn-reset" type="button">Reset</button>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Type of Import</label>
                        <div class="form-check">
                            <label class="form-check-label">
                                <!-- @TODO - make "checked" status a variable that has a default -->
                                <input checked class="form-check-input" type="checkbox" value="turbovote">
                                TurboVote Import
                            </label>
                        </div>
                    </div>
                    <div>
                        <input type="submit" class="btn btn-primary" value="Submit CSV">
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
