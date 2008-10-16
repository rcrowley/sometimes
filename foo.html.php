<?php
return html(
	S('div', array('id' => 'everything', 'class' => 'foo'),
		p(
			'This is a ',
			S('strong', array(), array('bold' => true),
				'bold'
			),
			S('span', array(), array('bold' => false),
				'plain'
			),
			' sentence.'
		),
		p(Sd('foo'))
	)
);
