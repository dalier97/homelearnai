<x-guest-layout>
    <h1>Registration Test</h1>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div>
            <label for="name">Name</label>
            <input id="name" type="text" name="name" required />
        </div>
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required />
        </div>
        <div>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required />
        </div>
        <div>
            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required />
        </div>
        <button type="submit">Register</button>
    </form>
</x-guest-layout>