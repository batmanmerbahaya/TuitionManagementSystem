{{-- Rows loaded into the "student list" modal on the teacher's subject page. --}}
@forelse ($students as $i => $s)
<tr><td>{{ $i + 1 }}</td><td>{{ $s->username }}</td><td>{{ $s->usercontact }}</td></tr>
@empty
<tr><td colspan='3' style='text-align:center;'>No students enrolled.</td></tr>
@endforelse
