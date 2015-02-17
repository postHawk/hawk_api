var HAWK_API = {
	ws: {
		/**
		 * текущий сокет
		 * @type WebSocket
		 */
		socket: null,
		/**
		 * статус сокета
		 * @type Boolean
		 */
		open: false
	},
	settings: {
		/**
		 * адрес сервиса
		 * @type string
		 */
		url: null,
		/**
		 * id пользователя
		 * @type string
		 */
		user_id: false
	},
	/**
	 * преобразование ошибок сервиса в строки
	 * @type object
	 */
	errors2string:{
		user_already_exists: 'Пользователь с таким идентификатором уже зарегистрирован в системе',
		invalid_key: 'Неверный ключ апи',
		user_not_register: 'Пользователь с таким идентификатором не зарегистрирован в системе',
		user_not_exists: 'Пользователь с таким идентификатором не найден',
		invalid_format_data: 'Неверный фомат данных',
		invalid_data: 'Общая ошибка данных. Ожидался POST-запрос',
		invalid_login_data: 'Не верный логин получателя',
		send_message_yourself: 'Нельзя отправить сообщение самому себе',
		invalid_login_format: 'Неверный формат идентификатора',
		domain_not_register: 'Данный домен не зарегистрирован в системе',
		user_not_online: 'Пользователь не в сети'
	},
	/**
	 * необходимость переинициализации
	 * @type Boolean
	 */
	reinitialization: false,

/**
 * метод инициализации подключения
 * @param {object} opt массив настроек
 * @returns {Boolean}
 * @todo а нужно ли переподключение при ошибке?
 */
	init: function(opt) {
		//если при подключении случилась ошибка, то пробуем переподключиться
		if(!HAWK_API.reinitialization)
		{
			if(!!WebSocket)
			{
				this.settings = $.extend(this.settings, opt);

				if(!this.settings.user_id)
				{
					this.print_error('необходимо указать user_id');
					return false;
				}

				//биндим контекст для методов
				this.init.bind(this);
				this.bind_handler.bind(this);
				this.bind_default_hadler.bind(this);
				this.send_message.bind(this);
				this.get_user_id.bind(this);
				this.check_on_error.bind(this);
				this.get_url.bind(this);
				this.bind_handler('hawk.open', function() {
					HAWK_API.set_user_id();
				});
			}
			else
			{
				this.print_error('Технология не поддерживается');
				return false;
			}
		}

		//создаём подключение
		HAWK_API.create_socket(HAWK_API.get_url());
		return true;

	},
	/**
	 * метод инициализирует подключение к сокету
	 * @param {string} url адрес для подключения
	 * @returns {void}
	 */
	create_socket: function(url){
		this.ws.socket = new WebSocket(url);
		this.bind_default_hadler('onopen', this.on_open);
		this.bind_default_hadler('onmessage', this.on_message);
		this.bind_default_hadler('onclose', this.on_close);
		this.bind_default_hadler('onerror', this.on_error);
	},
	/**
	 * метод отправки сообщения
	 * @param {object} msg
	 * @returns {void}
	 */
	send_message: function(msg) {
		this.ws.socket.send(msg);
		$(HAWK_API).trigger('hawk.msg_sended', msg);
	},
	/**
	 * метод устанавливает текущего пользователя
	 * @returns {void}
	 */
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
	/**
	 * проверка корректности логина пользователя
	 * @param {string} id логин
	 * @returns {Boolean}
	 */
	check_user_id: function(id) {
		return /^[a-zA-Z\d]{3,64}$/.test(id);
	},
	/**
	 * возвращает id пользователя
	 * @returns {HAWK_API.settings.user_id}
	 */
	get_user_id: function() {
		return this.settings.user_id;
	},
	/**
	 * возвращает url пользователя
	 * @returns {HAWK_API.settings.url}
	 */
	get_url: function () {
		return this.settings.url;
	},
	/**
	 * привязка обработчиков к событию
	 * @param {string} type
	 * @param {function} fn
	 * @returns {void}
	 */
	bind_handler: function(type, fn){
		$(this).on(type, fn);
	},
	/**
	 * привязка дефолтных обработчиков к событию
	 * @param {string} type
	 * @param {function} fn
	 * @returns {void}
	 */
	bind_default_hadler: function(type, fn)
	{
		if(typeof this.ws.socket[type] == 'object')
		{
			this.ws.socket[type] = fn;
		}
	},
	/**
	 * метод отвязывает обработчик события
	 * @param {string} type
	 * @returns {void}
	 */
	unbind_handler: function(type){
		if(typeof this.ws.socket[type] == 'object')
		{
			this.ws.socket[type] = null;
		}
	},
	/**
	 * дефолтный обработчик открытия сокета
	 * @returns {void}
	 */
	on_open: function(){
		//console.log('open');
		HAWK_API.reinitialization = false;
		HAWK_API.ws.open = true;
		$(HAWK_API).trigger('hawk.open');
	},
	/**
	 * дефолтный обработчик сообщения
	 * @param {object}
	 * @returns {void}
	 */
	on_message: function(e){
		try
		{
			var data = JSON.parse(e.data);
			$(HAWK_API).trigger('hawk.message', [data]);
//			console.log(data);
		}
		catch (ex)
		{
//			console.log(e.data);
		}

		HAWK_API.check_on_error(e.data);

	},
	/**
	 * дефолтный обработчик закрытия сокета
	 * @returns {void}
	 */
	on_close: function(){
//		console.log('close');
		HAWK_API.reinitialization = true;
		setTimeout(HAWK_API.init, 30000);
		$(HAWK_API).trigger('hawk.close');
	},
	/**
	 * дефолтный обработчик ошибки сокета
	 * @returns {void}
	 */
	on_error: function(){
//		console.log('error');
		$(HAWK_API).trigger('hawk.socket_error');
	},
	/**
	 * проверка сообщения на ошибки
	 * @param {object|string} msg ответ сервиса
	 * @returns {void}
	 */
	check_on_error: function(msg) {
		if(typeof this.errors2string[msg] !== 'undefined')
		{
			this.print_error(this.errors2string[msg]);
			$(HAWK_API).trigger('hawk.server_error', [this.errors2string[msg]]);
			HAWK_API.reinitialization = false;
		}
	},
	/**
	 * выводит сообщение об ошибке в консоль
	 * @param {string} text
	 * @returns {void}
	 */
	print_error: function(text){
		console.error(text);
	}
};
