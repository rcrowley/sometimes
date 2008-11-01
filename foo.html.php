<?php
return html(
	div(array('id' => 'everything', 'class' => 'foo'),
		p(
			'This is a ',
			strong(Sc('bold', true),
				'bold'
			),
			span(Sc('bold', false),
				'plain'
			),
			' sentence.'
		),
		p(Sd('foo')),
		ul(Sforeach(array('asdf' => 'qwerty', 'foo' => 'bar', 'hooah' => 'woo'),
			li(Sd('_k'), ': ', Sd('_v'))
		))
	)
);
