@extends('layouts.master')

@section('main_content')

<div>
  {{$data->links()}}
    <table class="table">
    @foreach($data as $key => $row)
      {{info($row->id)}}
        <tr class="row">
          <td class="col-md-2">
            <a href="failed-jobs/{{   $row->id  }}">{{$row->failed_at}}</a>
          </td>
          <td class="col-md-4">
            <strong>{{$row->commandName}}</strong>
            @isset($row->parameters)
              <ul>
                @foreach ($row->parameters as $key => $value)
                  <li><code>{{$key}}</code> {{$value}}</li>
                @endforeach
              </ul>
            @endif
          </td>
          <td class="col-md-6">
            {{substr($row->exception, 0, 255)}}...
          </td>    
        </tr>
    @endforeach
    </table>
  {{$data->links()}}
</div>

@stop
