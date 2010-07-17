{gt text='Verify your request to change your e-mail address at \'%1$s\'' tag1=$sitename assign='subject'}
{modgetvar module='Users' name='chgemail_expiredays' default=0 assign='expiredays'}
{gt text='Hello! You requested to change your e-mail address for user account \'%1$s\' at %2$s from %3$s to %4$s.' tag1=$uname tag2=$sitename tag3=$email tag4=$newemail} {gt text='You must confirm this change before it will take effect.'}{if $expiredays > 0} {gt text='If you do not confirm this change within the next day, the request will be deleted from our system.' plural='If you do not confirm this change within the next %1$s days, the request will be deleted from our system.' tag1=$expiredays count=$expiredays}{/if}

{gt text="You can confirm the e-mail address change by going to this URL with your browser: "} {$url}

{gt text="If you did not make this request then you do not need to take any action."} {gt text="The e-mail address wil not be changed unless the confirmation code is used, and you are the only recipient of this message. You can just delete this message."}