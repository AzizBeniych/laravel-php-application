<h2>Password Reset</h2>

<p>Hello {{ $user->getDisplayName() }},</p>
<p>Access the following link to change your password</p>
<p>{{ HTML::linkRoute('reset-password', null, compact('token')) }}<br/>
    This link will expire in {{ round(Config::get('auth.reminder.expire', 1440) / 60) }} hours.</p>

<p>Please do not reply to this automated email.<br/>
    Regards</p>
