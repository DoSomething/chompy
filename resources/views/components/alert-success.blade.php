@if (is_array($data))
    @foreach ($data as $entityName => $entityData)
        <li>{{ $entityName }}<ul>
            @foreach ($entityData as $fieldName => $value)
                <li>
                    {{ $fieldName }}: <strong>{{ is_array($value) ? json_encode($value) : $value }}</strong>
                </li>
            @endforeach
        </ul></li>
    @endforeach
@else
    {{ $data }}
@endif
