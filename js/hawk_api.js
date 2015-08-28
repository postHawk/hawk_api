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
		url: 'wss://post-hawk.com:2222',
		/**
		 * id пользователя
		 * @type string
		 */
		user_id: false,
		/**
		 * Шифровать передаваемые сообщения.
		 * @type Boolean
		 */
		encryption: {
			enabled: false,
			salt: 'a3453fsdf564l546asdff6mas,.fma.S<Dfm'
		}
	},
	/**
	 * преобразование ошибок сервиса в строки
	 * @type object
	 */
	errors2string:{
		invalid_api_key: 'Неверный ключ апи',
		user_not_register: 'Пользователь с таким идентификатором не зарегистрирован в системе',
		user_not_exists: 'Пользователь с таким идентификатором не найден',
		invalid_format_data: 'Неверный фомат данных',
		invalid_data: 'Общая ошибка данных. Ожидался POST-запрос',
		invalid_login_data: 'Не верный логин получателя',
		send_message_yourself: 'Нельзя отправить сообщение самому себе',
		invalid_login_format: 'Неверный формат идентификатора',
		domain_not_register: 'Данный домен не зарегистрирован в системе',
		user_not_online: 'Пользователь не в сети',
		general_error: 'Общая ошибка сервера',
		invalid_group_format: 'Неверный формат идентификатора группы',
		access_denied_to_group: 'Доступ к группе запрещён'
	},
	/**
	 * необходимость переинициализации
	 * @type Boolean
	 */
	reinitialization: false,
	/**
	 * Инициализирован ли уже чат
	 * @type Boolean
	 */
	initialized: false,
	/**
	 * Устновлено ли подключение с сервером
	 * @type Boolean
	 */
	id_setted: false,
	/**
	 * Очередь запросов
	 * Из-за проблемы в хроме все запросы придётся делать синхронно
	 * (если отправлять сразу пачку сообщений из одной вкладки хрома в другую, 
	 * то из 10 до сервера доходит три)
	 * @type Array
	 */
	queue: [],
	/**
	 * Состояние отправки сообщений
	 * @type Boolean
	 */
	in_process: false,
	/**
	 * Последняя возникшая ошибка
	 * @type HAWK_API.errors2string
	 */
	last_error: null,

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
				this.settings = $.extend(true, this.settings, opt);

				if(!('CryptoJS' in window))
				{
					this.settings.encryption.enabled = false;
					console.warn('Отсутствует CryptoJS. Шифрование не возможно.')
				}

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

			this.initialized = true;
		}

		if($('#hawk_fix_ssl').size())
		{
			$('#hawk_fix_ssl').remove();
		}
		
		if(!HAWK_API.last_error)
		{
			//создаём подключение
			HAWK_API.create_socket(HAWK_API.get_url());
		}
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
	 * @param {Boolean} sync синхронно или асинхронно отправлять сообщения
	 * @returns {void}
	 */
	send_message: function(msg, sync) {

		if(typeof sync === 'undefined')
		{
			sync = true;
		}

		if(this.in_process && sync)
		{
			this.queue.push(msg);
			return;
		}

		this.in_process = true;

		if(typeof msg == 'object')
		{
			msg.from = this.get_user_id();
			msg.hawk_action = msg.action || 'send_message';
			delete msg.action;
			msg.domains = msg.domains || [document.location.host];

			if(this.settings.encryption.enabled && typeof CryptoJS !== 'undefined'
					&& typeof CryptoJS.AES !== 'undefined' && typeof CryptoJS.enc.Base64 !== 'undefined'
					&& msg.hasOwnProperty('text') && msg.text !== '')
			{
				msg.text = CryptoJS
						.AES.encrypt(JSON.stringify(msg.text), this.settings.encryption.salt, { format: HAWK_API })
						.toString();
			}

			msg = JSON.stringify(msg);
		}

		this.ws.socket.send(msg);
		$(HAWK_API).trigger('hawk.msg_sended', msg);
	},
	/**
	 * Получение списка публичных групп.
	 * @param {array} domains массив доменов для обработки
	 * @param {string} event название события (по-умолчанию get_group_list)
	 * @returns {void}
	 */
	get_group_list: function(domains, event) {
		domains = domains || [document.location.host];
		event = event || 'get_group_list';
		var msg = {
			id: this.get_user_id(),
			domains: domains,
			action: 'get_group_list',
			event: event
		};

		this.send_message(msg);
	},
	/**
	 * Получение списка пользваоетелей по группам
	 * @param {array} groups массив групп
	 * @param {array} domains массив доменов для обработки
	 * @param {string} event название события (по-умолчанию get_by_group)
	 * @returns {void}
	 */
	get_users_by_group: function(groups, domains, event) {

		if(groups.length)
		{
			domains = domains || [document.location.host];
			event = event || 'get_by_group';
			var msg = {
				id: this.get_user_id(),
				domains: domains,
				groups: groups,
				action: 'get_by_group',
				event: event
			};

			this.send_message(msg);
		}
		else
		{
			this.print_error('Список групп должен быть не пустым массивом');
		}
	},
	/**
	 * Добавление пользователя в группы
	 * создание новых групп происходит автоматически.
	 * Группа создаётся с публичным доступом
	 *
	 * @param {array} groups массив групп
	 * @param {string} id логин пользователя
	 * @param {array} domains массив доменов для обработки
	 * @param {string} event название события (по-умолчанию add_in_groups)
	 * @returns {void}
	 */
	add_user_to_group: function(groups, id, domains, event) {
		domains = domains || [document.location.host];
		event = event || 'add_in_groups';
		id = id || this.get_user_id();
		if(typeof groups == 'object' && groups.length)
		{
			var msg = {
				id: id,
				groups: groups,
				domains: domains,
				action: 'add_in_groups',
				event: event
			};

			this.send_message(msg);
		}
	},
	/**
	 * Удаление пользователя из группы.
	 * Пустые группы удаляются автоматически.
	 *
	 * @param {array} groups массив групп
	 * @param {string} id id пользователя
	 * @param {array} domains массив доменов для обработки
	 * @param {string} event название события (по-умолчанию add_in_groups)
	 * @returns {void}
	 */
	remove_user_from_group: function(groups, id, domains, event) {
		domains = domains || [document.location.host];
		event = event || 'remove_from_groups';
		id = id || this.get_user_id();
		if(typeof groups == 'object' && groups.length)
		{
			var msg = {
				id: id,
				groups: groups,
				domains: domains,
				action: 'remove_from_groups',
				event: event
			};

			this.send_message(msg);
		}
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
	 * возвращает url сервера
	 * @returns {HAWK_API.settings.url}
	 */
	get_url: function () {
		return this.settings.url;
	},
	/**
	 * привязка обработчиков к событию
	 * @param {string} type название события
	 * @param {function} fn колбэк
	 * @returns {void}
	 */
	bind_handler: function(type, fn){
		if(type.search('hawk.') === -1)
		{
			type = 'hawk.' + type;
		}
		
		$(this).on(type, fn);
	},
	/**
	 * привязка дефолтных обработчиков к событию
	 * @param {string} type название события
	 * @param {function} fn колбэк
	 * @returns {void}
	 */
	bind_default_hadler: function(type, fn)
	{
		if(typeof this.ws.socket[type] === 'object')
		{
			this.ws.socket[type] = fn;
		}
	},
	/**
	 * метод отвязывает обработчик события
	 * @param {string} type название события
	 * @returns {void}
	 */
	unbind_handler: function(type){
		if(typeof this.ws.socket[type] === 'object')
		{
			this.ws.socket[type] = null;
		}
	},
	/**
	 * дефолтный обработчик открытия сокета
	 * @returns {void}
	 */
	on_open: function(){
		
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
		var data = JSON.parse(e.data);

		if(!data.hasOwnProperty('error') || data.error === false)
		{
			if(HAWK_API.settings.encryption.enabled && typeof CryptoJS !== 'undefined'
				&& typeof CryptoJS.AES !== 'undefined' && typeof CryptoJS.enc.Base64 !== 'undefined'
				&& data.hasOwnProperty('text') && data.text !== '')
			{
				data.text = JSON.parse(CryptoJS
						.AES.decrypt(data.text, HAWK_API.settings.encryption.salt, { format: HAWK_API })
						.toString(CryptoJS.enc.Utf8));
			}

			var event_type = 'hawk.message';
			if(data.hasOwnProperty('from') && data.from === 'hawk_server')
			{
				event_type = 'hawk.server_message';
			}

			$(HAWK_API).trigger(event_type, [data]);

			if(!HAWK_API.id_setted)
			{
				HAWK_API.id_setted = true;
				$(HAWK_API).trigger('hawk.initialized');
			}

			if(data.event)
			{
				if(data.event.search('hawk.') === -1)
				{
					data.event = 'hawk.' + data.event;
				}
				$(HAWK_API).trigger(data.event, [data]);
			}
//			console.log(data);
		}
		else
		{
			HAWK_API.check_on_error(data.error);
		}

		if(HAWK_API.queue.length)
		{
			HAWK_API.send_message(HAWK_API.queue.shift(), false);
		}
		else
		{
			HAWK_API.in_process = false;
		}

	},
	/**
	 * дефолтный обработчик закрытия сокета
	 * @returns {void}
	 */
	on_close: function(e){
//		console.log('close');
		if(e.code === 1006 || e.code === 1015)
		{
			HAWK_API.fix_ssl();
		}
		else
		{
			setTimeout(HAWK_API.init, 30000);
			$(HAWK_API).trigger('hawk.close');
		}
		HAWK_API.reinitialization = true;
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
			HAWK_API.last_error = this.errors2string[msg];
		}
	},
	/**
	 * выводит сообщение об ошибке в консоль
	 * @param {string} text
	 * @returns {void}
	 */
	print_error: function(text){
		console.error(text);
	},
	/**
	 * форматирование объекта для шифрования
	 * @param {CryptoJS.lib.CipherParams} cipherParams параметры шифрования
	 * @returns {String}
	 */
	stringify: function (cipherParams) {
		// create json object with ciphertext
		var jsonObj = {
			ct: cipherParams.ciphertext.toString(CryptoJS.enc.Base64)
		};

		// optionally add iv and salt
		if (cipherParams.iv) {
			jsonObj.iv = cipherParams.iv.toString();
		}
		if (cipherParams.salt) {
			jsonObj.s = cipherParams.salt.toString();
		}

		// stringify json object
		return JSON.stringify(jsonObj);
	},
	/**
	 * форматирование объекта для расшифровки
	 * @param {string} jsonStr строка для расшифровки
	 * @returns {CryptoJS.lib.CipherParams}
	 */
	parse: function (jsonStr) {
		// parse json string
		var jsonObj = JSON.parse(jsonStr);

		// extract ciphertext from json object, and create cipher params object
		var cipherParams = CryptoJS.lib.CipherParams.create({
			ciphertext: CryptoJS.enc.Base64.parse(jsonObj.ct)
		});

		// optionally extract iv and salt
		if (jsonObj.iv) {
			cipherParams.iv = CryptoJS.enc.Hex.parse(jsonObj.iv)
		}
		if (jsonObj.s) {
			cipherParams.salt = CryptoJS.enc.Hex.parse(jsonObj.s)
		}

		return cipherParams;
	},
	/**
	 * Если обращение идёт не на стандартный порт ssl, 
	 * то браузер не может через wss получить сертификат
	 * и возникает ошибка. Чтобы восстановить сертификат нужно обратиться на
	 * соответсвующий адрес напрямую.
	 * @returns {void}
	 */
	fix_ssl: function(){
		$('body').append('<iframe id="hawk_fix_ssl" onload="HAWK_API.init()" style="display: none" src="' + HAWK_API.settings.url.replace('wss', 'https') + '">');
	}
};