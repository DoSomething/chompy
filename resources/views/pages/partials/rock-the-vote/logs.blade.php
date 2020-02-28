<table class="table">
    <thead>
        <tr class="row">
            <th class="col-md-2">Started Registration</th>
            <th class="col-md-2">{{$user_id ? 'Import File' : 'User'}}</th>
            <th class="col-md-1">Status</th>
            <th class="col-md-3">Tracking Source</th>
            <th class="col-md-2">Finish With State</th>
            <th class="col-md-2">Pre-Registered</th>
        </tr>
    </thead>
    @foreach($rows as $key => $row)
        <tr class="row">
            <td class="col-md-2">
                {{$row->started_registration}}
            </td>    
            <td class="col-md-2">
                <a href="{{$user_id ? '/import-files/' . $row->import_file_id : '/users/' . $row->user_id}}">
                    {{$user_id ? $row->import_file_id : $row->user_id}}
                </a>
            </td> 
            <td class="col-md-1">
                {{$row->status}}
            </td>
            <td class="col-md-3">
                <ul>
                    @foreach(explode(',', $row->tracking_source) as $attribute)
                        <li>{{$attribute}}</li>
                    @endforeach
                </ul>
            </td>
            <td class="col-md-2">
                {{$row->finish_with_state}}
            </td>
            <td class="col-md-2">
                {{$row->pre_registered}}
            </td> 
        </tr>
    @endforeach
</table>
{{$rows->links()}}
