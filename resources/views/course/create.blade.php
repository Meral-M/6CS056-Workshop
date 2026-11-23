<!DOCTYPE html>
<html>
<head>
    <title>Create Course</title>
</head>
<body>

<h1>Create Course</h1>

@include('partials.errors')

<form action="/courses" method="POST">
    @csrf
    @include('course._fields')
    <button type="submit">Create Course</button>
</form>

<br>

<a href="/courses">Back to Courses</a>

</body>
</html>
