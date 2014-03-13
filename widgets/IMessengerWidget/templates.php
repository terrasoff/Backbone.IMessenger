<div id="<?php echo $id;?>" class="messenger">

    <div class="control">
        <div class="control-btn-new"><button>Написать</button></div>
        <div class="control-btn-conversations">Разговоры</div>
        <div class="control-btn-peers">Активные разговоры</div>
    </div>
    <div class="conversations"></div>
    <div class="peers">
        <div class="tabs"><div class="last"></div></div>
        <div class="items"></div>
    </div>
    <div class="new-messages"></div>
</div>

<script type="text/x-underscore-template" id="template_messenger_receiver_list">
    <div class="items"></div><br style="clear" />
</script>

<script type="text/x-underscore-template" id="template_autocomplete">
    <div class="autocomplete">
        <input class="autocomplete-filter" type="text" placeholder="введите имя пользователя" />
        <div class="autocomplete-list"></div>
        <div class="autocomplete-default"></div>
    </div>
</script>

<script type="text/x-underscore-template" id="template_messenger_new">
    <h2>Новое сообщение</h2>
    <div class="new-message-receivers"></div>
    <div class="new-message-title"><input type="text" placeholder="title" /></div>
    <div class="new-message-body"><textarea placeholder="text"></textarea></div>
    <button class="new-message-send">Отправить</button>
</script>

<script type="text/x-underscore-template" id="template_messenger_receiver_listitem">
    <div><%- model.getName()%></div>
</script>

<script type="text/x-underscore-template" id="template_messenger_receiver">
    <% if (model.isNew()) { %>
        <div class="item-add">Добавить</div>
    <% } else { %>
        <div class="item-name"><%- model.getName() %></div>
        <div class="item-delete">x</div>
    <% } %>
</script>

<script type="text/x-underscore-template" id="template_messenger_peer">
    <div class="peer-messages"></div>
    <textarea class="peer-textarea"></textarea>
    <button class="peer-send">Отправить</button>
</script>

<script type="text/x-underscore-template" id="template_messenger_message">
    <span class="peer-message-user"><%- user.getName() %></span>
    <span class="peer-message-body"><%- model.getBody() %></span>
</script>

<script type="text/x-underscore-template" id="template_messenger_conversation">
    <div class="conversation-user"><%- model.getTitle() %></div>
    <div class="conversation-message">
        <div class="conversation-title"><%- model.getLastMessage().getTitle() %></div>
        <div class="conversation-body"><%- model.getLastMessage().getBody() %></div>
    </div>
</script>

<script type="text/x-underscore-template" id="template_messenger_conversation_tab">
    <div class="tabs-name"><%- model.title %></div>
    <div class="tabs-close">x</div>
</script>