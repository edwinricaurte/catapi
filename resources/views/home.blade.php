<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Vote for your favorite Cat</title>
        <link rel="stylesheet" type="text/css" href="/styles.css">
        <meta name="_token" content="{{csrf_token()}}">
    </head>
    <body>
        <header>
            <div class="left-bottom-curner"></div>
            <img src="/imgs/isg-logo-d.webp" class="isg-logo" alt="ISG Logo">
            <button class="reset" onclick="resetVotes()"><img src="/imgs/svg/reset.svg"></button>
        </header>
        <section id="cats_list" class="cats">
            @foreach($cats_list as $cat)
                <div class="cat" data-id="{{$cat->id}}" id="cat_{{$cat->id}}">
                    <div class="image" style="background-image: url('{{$cat->url}}')"></div>
                    <div class="details">
                        <p class="name">{{$cat->breeds->name}}</p>
                        <p class="origin"><img src="/imgs/svg/location.svg" width="20"> {{$cat->breeds->origin}}</p>
                        <p class="temperament">{{$cat->breeds->temperament}}</p>
                    </div>

                    <div class="buttons">
                        <button class="l-btn dislike-btn" data-value="dislike" type="button"><img src="/imgs/svg/dislike.svg" width="40"></button>
                        <button class="l-btn like-btn" data-value="like" type="button"><img src="/imgs/svg/like.svg" width="40"></button>
                    </div>
                </div>
            @endforeach
        </section>
    </body>
<script>
    async function catVote(btn){
        let cat_element = btn.currentTarget.closest('.cat');
        cat_element.querySelector('.l-btn.dislike-btn').setAttribute('disabled',true);
        cat_element.querySelector('.l-btn.like-btn').setAttribute('disabled',true);

        let vote_object = {};
        vote_object.cat_id = cat_element.getAttribute('data-id');
        vote_object.value = btn.currentTarget.getAttribute('data-value');

        response = await sendPostRequest('/vote/',vote_object);

        if(response.status == 1){
            let cat = document.getElementById('cat_'+vote_object.cat_id);
            cat.querySelector('div.buttons').innerHTML = '';
            let dislike_div = document.createElement('div');
            dislike_div.classList.add('dislikes');
            dislike_div.textContent = '';
            let like_div = document.createElement('div');
            like_div.textContent = '';
            like_div.classList.add('likes');
            let user_selection = document.createElement('img');
            user_selection.classList.add('user-selection')
            switch (vote_object.value){
                case 'like':
                    user_selection.classList.add('user-selection')
                    user_selection.classList.add('liked')
                    user_selection.src = '/imgs/svg/like.svg';
                    break;
                case ('dislike'):
                    user_selection.classList.add('user-selection')
                    user_selection.src = '/imgs/svg/dislike.svg';
                    break;
            }
            await cat.querySelector('div.buttons').appendChild(dislike_div);
            await cat.querySelector('div.buttons').appendChild(user_selection);
            await cat.querySelector('div.buttons').appendChild(like_div);
            getVotesSummary();
        } else {
            cat_element.querySelector('.l-btn.dislike-btn').removeAttribute('disabled');
            cat_element.querySelector('.l-btn.like-btn').removeAttribute('disabled');
        }
    }

    async function getMyVotes(){
        response = await sendPostRequest('/my-votes/');
        if(response.my_votes){
            if(response.my_votes.length>0){
                for(r_vote of response.my_votes){
                    let cat = document.getElementById('cat_'+r_vote.image_id);
                    cat.querySelector('div.buttons').innerHTML = '';
                    let dislike_div = document.createElement('div');
                    dislike_div.classList.add('dislikes');
                    dislike_div.textContent = '';
                    let like_div = document.createElement('div');
                    like_div.textContent = '';
                    like_div.classList.add('likes');
                    let user_selection = document.createElement('img');
                    switch (true){
                        case (r_vote.value>0):
                            user_selection.classList.add('user-selection')
                            user_selection.classList.add('liked')
                            user_selection.src = '/imgs/svg/like.svg';
                            break;
                        case (r_vote.value<0):
                            user_selection.classList.add('user-selection')
                            user_selection.src = '/imgs/svg/dislike.svg';
                            break;
                    }
                    await cat.querySelector('div.buttons').appendChild(dislike_div);
                    await cat.querySelector('div.buttons').appendChild(user_selection);
                    await cat.querySelector('div.buttons').appendChild(like_div);
                }
            }
            getVotesSummary();
        }
    }

    async function getVotesSummary(){
        response = await sendPostRequest('/votes-summary/');
        if(response.votes){
            if(response.votes.length>0){
                for(cat_vote of response.votes){
                    cat_container = document.getElementById('cat_'+cat_vote.cat_id);
                    if(cat_container.querySelectorAll('.buttons.ready-to-vote').length==1 && cat_container.querySelectorAll('.buttons.ready-to-vote .likes').length>0){
                        if(cat_vote.dislikes){
                            cat_container.querySelector('.dislikes').textContent = cat_vote.dislikes;
                        } else {
                            cat_container.querySelector('.dislikes').textContent = 0;
                        }
                        if(cat_vote.likes){
                            cat_container.querySelector('.likes').textContent = cat_vote.likes;
                        } else {
                            cat_container.querySelector('.likes').textContent = 0;
                        }
                    }
                }
            }
        }
    }

    async function resetVotes(){
        let reset_btn = document.querySelector('header button.reset');
        reset_btn.innerHTML = '<img class="rotate" src="/imgs/svg/loading.svg">';
        document.getElementById('cats_list').classList.add('slow-transition');
        document.getElementById('cats_list').classList.add('grayscale');

        response = await sendPostRequest('/reset-votes');
        if(response.status = 1){
            window.location.href = window.location.href;
        }
    }

    async function sendPostRequest(url = null, data = {}) {
        data._token = document.querySelector('meta[name="_token"]').content;
        data.user_id = '{{Session::getId()}}';
        const response = await fetch(url, {
            method: 'POST',
            mode: 'cors',
            cache: 'no-cache',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            redirect: 'follow',
            referrerPolicy: 'no-referrer',
            body: JSON.stringify(data)
        });
        try {
            if(response.status == 200){
                let json_format = await response.json();
                return json_format;
            } else {
                console.log("ERROR");
                console.log(response);
            }
        } catch (error) {
            console.log(error);
        }
    }

    document.addEventListener("DOMContentLoaded",async function() {
        await getMyVotes();
        document.querySelectorAll('.cat .buttons button.l-btn').forEach(function(button){
            button.addEventListener('click',catVote)
        });
        document.querySelectorAll('.cat .buttons').forEach(function(button_container){
            button_container.classList.add('ready-to-vote');
        });
    });
</script>
</html>
