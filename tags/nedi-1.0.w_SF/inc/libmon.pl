#============================================================================
#
# Program: libmon.pl
# Programmer: Remo Rickli
#
# -> Functions for monitoring <-
#
#============================================================================
package mon;

#===================================================================
# Send SMS
#===================================================================
sub SendSMS {
	print "$_[0]\n";
}

#===================================================================
# Send Mail
#===================================================================
sub SendMail {

	use Net::SMTP;
	
	undef (%main::usr);
	&db::ReadUser('mon','1');

	print " mailing \"$_[0]\" to:" if $main::opt{v};

	foreach my $u (keys %main::usr){
		if($main::usr{$u}{ml}){
			print " $u" if $main::opt{v};
			
			# send the message
			my $message = Net::SMTP->new($misc::smtpserver) || die "can't talk to SMTP server $misc::smtpserver\n";
			$message->mail($misc::mailfrom);
			$message->to($main::usr{$u}{ml}) || die "failed to send to $main::usr{$u}{ml}!";
			$message->data();
			$message->datasend("To: $main::usr{$u}{ml}\n");
			$message->datasend("From: $misc::mailfrom\n");
			$message->datasend("Subject: $_[0]\n");
			$message->datasend("\n");
			$message->datasend("$_[1]\n");
			$message->dataend();
			$message->quit;
		}
	}
	print "\n" if $main::opt{v};
}


1;
