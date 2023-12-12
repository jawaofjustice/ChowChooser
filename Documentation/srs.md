# ChowChooser Software Requirements Specification

## 1.4: Product Scope

The purpose of ChowChooser is to make it easier for groups of people to choose a restaurant and order their food. Office employees often want to share a meal together, either to celebrate a special occasion or just as an opportunity to get to know the other members of their team a little better. It can be very tedious to gather everyone’s order, and typically the restaurant chosen is not everyone’s first choice.

With ChowChooser, every employee that wants to share a meal together votes on their choice of restaurant, and whichever restaurant gets the most votes is the one that everyone goes to. That way, no one feels excluded from the decision process of choosing a restaurant. Then everyone enters their order on the ChowChooser website, which is a lot more efficient than having someone stop at everyone’s desk and collect their orders. Paying for the meal can also be coordinated, which saves a lot of time compared to waiting for everyone to pay their bills individually at a restaurant.

## 2.2 Product Functions

ChowChooser hosts user-generated lobbies in which lobby participants can vote for a restaurant to order food from, place orders from the winning restaurant, and view a summary of their order(s).

### 2.2.1 Create a lobby

Users can create a lobby with a custom name, a list of restaurants to vote on, a voting phase end time, and an ordering phase end time.

The only optional field is the voting phase end time; creating a lobby without one skips directly to the ordering phase.

### 2.2.2 Invite users to a lobby

Users can be invited to a lobby with a randomized code generated for each lobby.

Users input lobby invite codes in the main menu in order to join a lobby.

### 2.2.3 View a lobby

Users can view lobbies that they are members of.

The "View Lobby" page displays phase-dependent lobby information:

+ Voting phase: displays a list of restaurants to vote on.
+ Ordering phase: displays a user's placed orders.
  + The lobby admin is shown orders from all users.
+ Completed phase: summarizes the user's final order(s).
  + The lobby admin is shown orders from all users.

#### 2.2.3.1 Vote for restaurants

During a lobby's voting phase, users can cast a vote on one restaurant from the list.

A user can recast their vote during this phase.

#### 2.2.3.2 Place and edit orders

During a lobby's ordering phase, users can place orders for whichever restaurant won the voting phase.

Orders can also be edited and removed during this phase.
