var HAWK_API = {
	ws: {
		socket: null,
		open: false
	},
	settings: {
		url: 'ws://127.0.0.1:2222',
		user_id: false
	},
	errors2string:{
		user_already_exists: 'Пользователь с таким идентификатором уже зарегистрирован в системе',
		invalid_key: 'Неверный ключ апи',
		user_not_register: 'Пользователь с таким идентификатором не зарегистрирован в системе',
		user_not_exists: 'Пользователь с таким идентификатором не найден',
		invalid_format_data: 'Неверный фомат данных',
		invalid_data: 'Общая ошибка данных. Ожидался POST-запрос',
		invalid_login_data: 'Не верный логин получателя',
		send_message_yourself: 'Нельзя отправить сообщение самому себе',
		invalid_login_format: 'Неверный формат идентификатора'
	},
	reinitialization: false,

	init: function(opt) {

		if(!HAWK_API.reinitialization)
		{
			this.settings = $.extend(this.settings, opt);

			if(!this.settings.user_id)
			{
				this.print_error('need set user_id property');
				return false;
			}

			this.init.bind(this);
			this.bind_handler.bind(this);
			this.bind_default_hadler.bind(this);
			this.send_message.bind(this);
			this.get_user_id.bind(this);
			this.check_on_error.bind(this);
			this.bind_handler('hawk.open', function() {
				HAWK_API.set_user_id();
			});
		}

		HAWK_API.create_socket(HAWK_API.settings.url);

	},
	create_socket: function(url){
		this.ws.socket = new WebSocket(url);
		this.bind_default_hadler('onopen', this.on_open);
		this.bind_default_hadler('onmessage', this.on_message);
		this.bind_default_hadler('onclose', this.on_close);
		this.bind_default_hadler('onerror', this.on_error);
	},
	send_message: function(msg) {
		this.ws.socket.send(msg);
		$(HAWK_API).trigger('hawk.msg_sended');
	},
	set_user_id: function() {
		if(this.check_user_id(this.settings.user_id))
		{
			this.send_message(this.settings.user_id);
		}
		else
		{
			this.print_error(this.errors2string.invalid_login_format);
		}
	},
	check_user_id: function(id) {
		return /^[a-zA-Z\d]{3,64}$/.test(id);
	},
	get_user_id: function() {
		return this.settings.user_id;
	},
	bind_handler: function(type, fn){
		$(this).on(type, fn);
	},
	bind_default_hadler: function(type, fn)
	{
		if(typeof this.ws.socket[type] == 'object')
		{
			this.ws.socket[type] = fn;
		}
	},
	unbind_handler: function(type){
		if(typeof this.ws.socket[type] == 'object')
		{
			this.ws.socket[type] = null;
		}
	},
	on_open: function(e){
		console.log('open');
		HAWK_API.reinitialization = false;
		HAWK_API.ws.open = true;
		$(HAWK_API).trigger('hawk.open');
	},
	on_message: function(e){
		try
		{
			var data = JSON.parse(e.data);
			$(HAWK_API).trigger('hawk.message', [data]);
			console.log(data);
		}
		catch (ex)
		{
			console.log(e.data);
		}

		HAWK_API.check_on_error(e.data);

	},
	on_close: function(e){
		console.log('close');
		HAWK_API.reinitialization = true;
		setTimeout(HAWK_API.init, 30000);
		$(HAWK_API).trigger('hawk.close');
	},
	on_error: function(e){
		console.log('error');
		$(HAWK_API).trigger('hawk.socket_error');
	},
	check_on_error: function(msg) {
		if(typeof this.errors2string[msg] !== 'undefined')
		{
			this.print_error(this.errors2string[msg]);
			$(HAWK_API).trigger('hawk.server_error');
		}
	},
	print_error: function(text){
		console.error(text);
	}
};
