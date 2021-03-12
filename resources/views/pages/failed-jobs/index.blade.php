@extends('layouts.master')

@section('title', 'Failed jobs')

@section('main_content')

<div>
  {{$data->links()}}

  <table class="table">

    @foreach($data as $key => $row)
      @php ($failedJob = parse_failed_job($row))

      <tr class="row">
        <td class="col-md-2">
          <a href="/failed-jobs/{{$failedJob['id']}}">
            {{$failedJob['failed_at']}}
          </a>
        </td>

        <td class="col-md-4">
          <strong>{{$failedJob['command_name']}}</strong>

          <ul>
              @foreach ($failedJob['parameters'] as $key => $value)
                  <li><code>{{$key}}</code> {{is_array($value) ? print_r($value, true) : $value}}</li>
              @endforeach
          </ul>
        </td>

        <td class="col-md-6">
          {{$failedJob['error_message']}}
        </td>    
      </tr>
    @endforeach

  </table>

  {{$data->links()}}
</div>

@stop
